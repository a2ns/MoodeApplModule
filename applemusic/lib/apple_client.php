<?php
/*
 * moOde Apple Music module - Apple Music API client
 *
 * Minimal proxy client for the Apple Music API (catalog + library).
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 */

namespace Applemusic;

class AppleClient
{
    const API_BASE = 'https://api.music.apple.com/v1/';

    private $developerToken;
    private $userToken;

    public function __construct($developerToken, $userToken = null)
    {
        $this->developerToken = $developerToken;
        $this->userToken = $userToken;
    }

    public function request($method, $path, $query = array(), $body = null)
    {
        $url = self::API_BASE . ltrim($path, '/');
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = array(
            'Authorization: Bearer ' . $this->developerToken,
            'Accept: application/json',
            'Content-Type: application/json',
        );
        if ($this->userToken !== null) {
            $headers[] = 'Media-User-Token: ' . $this->userToken;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Apple Music API request failed: ' . $curlError);
        }

        $data = json_decode($response, true);
        if ($status >= 400) {
            $message = isset($data['errors'][0]['detail']) ? $data['errors'][0]['detail'] : 'HTTP ' . $status;
            throw new \RuntimeException('Apple Music API error: ' . $message, $status);
        }

        return $data;
    }

    public function get($path, $query = array())
    {
        return $this->request('GET', $path, $query);
    }

    /*
     * Catalog search (songs, albums, artists)
     */
    public function searchCatalog($storefront, $term, $limit = 25)
    {
        return $this->get('catalog/' . $storefront . '/search', array(
            'term' => $term,
            'types' => 'songs,albums,artists',
            'limit' => $limit,
        ));
    }

    /*
     * User library: playlists
     */
    public function libraryPlaylists($limit = 50, $offset = 0)
    {
        return $this->get('me/library/playlists', array(
            'limit' => $limit,
            'offset' => $offset,
        ));
    }

    /*
     * User library: songs ("library songs")
     */
    public function librarySongs($limit = 50, $offset = 0)
    {
        return $this->get('me/library/songs', array(
            'limit' => $limit,
            'offset' => $offset,
        ));
    }

    /*
     * Tracks of a user library playlist
     */
    public function libraryPlaylistTracks($playlistId, $limit = 100, $offset = 0)
    {
        return $this->get('me/library/playlists/' . $playlistId . '/tracks', array(
            'limit' => $limit,
            'offset' => $offset,
        ));
    }
}
