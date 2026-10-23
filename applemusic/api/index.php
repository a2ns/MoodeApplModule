<?php
/*
 * moOde Apple Music module - HTTP API endpoint
 *
 * Serves JSON for the module UI:
 *   GET ?action=status          - module status (configured, authorized)
 *   GET ?action=devtoken        - developer token for MusicKit JS
 *   GET ?action=playlists       - user library playlists
 *   GET ?action=playlist&id=..  - tracks of a user library playlist
 *   GET ?action=library         - user library songs
 *   GET ?action=search&term=.. - catalog search (songs/albums/artists)
 *   POST ?action=authtoken      - store MusicKit media user token
 *   POST ?action=logout         - forget stored media user token
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 */

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/developer_token.php';
require_once __DIR__ . '/../lib/apple_client.php';

use Applemusic\Config;
use Applemusic\DeveloperToken;
use Applemusic\AppleClient;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail($message, $status = 500)
{
    respond(array('error' => $message), $status);
}

try {
    $configPath = getenv('APPLEMUSIC_CONFIG') ?: '/var/local/www/applemusic/config.json';
    $config = new Config($configPath);

    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($action === 'devtoken' || $action === 'status') {
        if (!$config->isConfigured()) {
            if ($action === 'status') {
                respond(array(
                    'configured' => false,
                    'authorized' => false,
                    'storefront' => $config->storefront(),
                ));
            }
            fail('Module not configured: missing team_id / key_id / private_key in config.json', 503);
        }
    }

    switch ($action) {
        case 'status':
            respond(array(
                'configured' => true,
                'authorized' => $config->getUserToken() !== null,
                'storefront' => $config->storefront(),
            ));
            break;

        case 'devtoken':
            $devToken = new DeveloperToken(
                $config->get('team_id'),
                $config->get('key_id'),
                $config->get('private_key_path'),
                $config->get('data_dir') . '/devtoken_cache.json'
            );
            respond(array('developerToken' => $devToken->get()));
            break;

        case 'authtoken':
            if ($method !== 'POST') {
                fail('POST required', 405);
            }
            $body = json_decode(file_get_contents('php://input'), true);
            $token = isset($body['token']) ? trim($body['token']) : '';
            if ($token === '') {
                fail('Missing token', 400);
            }
            $config->setUserToken($token);
            respond(array('ok' => true));
            break;

        case 'logout':
            if ($method !== 'POST') {
                fail('POST required', 405);
            }
            $path = $config->userTokenPath();
            if (file_exists($path)) {
                unlink($path);
            }
            respond(array('ok' => true));
            break;

        case 'playlists':
        case 'playlist':
        case 'search':
        case 'library':
            $userToken = $config->getUserToken();
            $needsUserToken = in_array($action, array('playlists', 'playlist'), true);
            if ($needsUserToken && $userToken === null) {
                fail('Not authorized: connect your Apple Music account first', 401);
            }

            $devToken = new DeveloperToken(
                $config->get('team_id'),
                $config->get('key_id'),
                $config->get('private_key_path'),
                $config->get('data_dir') . '/devtoken_cache.json'
            );

            $client = new AppleClient($devToken->get(), $userToken);

            if ($action === 'playlists') {
                $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
                $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
                respond($client->libraryPlaylists($limit, $offset));
            } elseif ($action === 'playlist') {
                $id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9\.\-]/', '', $_GET['id']) : '';
                if ($id === '') {
                    fail('Missing playlist id', 400);
                }
                respond($client->libraryPlaylistTracks($id));
            } elseif ($action === 'search') {
                $term = isset($_GET['term']) ? trim($_GET['term']) : '';
                if ($term === '') {
                    fail('Missing search term', 400);
                }
                respond($client->searchCatalog($config->storefront(), $term));
            } else { // library songs
                respond($client->librarySongs());
            }
            break;

        default:
            fail('Unknown action', 400);
    }
} catch (RuntimeException $e) {
    fail($e->getMessage(), $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
}
