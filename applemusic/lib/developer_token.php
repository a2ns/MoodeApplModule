<?php
/*
 * moOde Apple Music module - Apple Developer Token (JWT) generator
 *
 * Generates the ES256-signed JWT used as Developer Token for the
 * Apple Music API and MusicKit JS.
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 */

namespace Applemusic;

class DeveloperToken
{
    private $teamId;
    private $keyId;
    private $privateKeyPath;
    private $cachePath;
    private $ttl;

    public function __construct($teamId, $keyId, $privateKeyPath, $cachePath, $ttl = 86400 * 30)
    {
        $this->teamId = $teamId;
        $this->keyId = $keyId;
        $this->privateKeyPath = $privateKeyPath;
        $this->cachePath = $cachePath;
        $this->ttl = $ttl;
    }

    private function base64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function es256Sign($data, $pem)
    {
        $key = openssl_pkey_get_private($pem);
        if ($key === false) {
            throw new \RuntimeException('Cannot load Apple private key: ' . openssl_error_string());
        }
        $signature = '';
        $ok = openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        if (!$ok) {
            throw new \RuntimeException('Failed to sign developer token: ' . openssl_error_string());
        }
        return $this->base64url($signature);
    }

    public function generate()
    {
        $header = $this->base64url(json_encode(array('alg' => 'ES256', 'kid' => $this->keyId)));
        $now = time();
        $payload = $this->base64url(json_encode(array(
            'iss' => $this->teamId,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        )));
        $signingInput = $header . '.' . $payload;

        $pem = file_get_contents($this->privateKeyPath);
        if ($pem === false) {
            throw new \RuntimeException('Cannot read Apple private key file: ' . $this->privateKeyPath);
        }
        $signature = $this->es256Sign($signingInput, $pem);

        return $signingInput . '.' . $signature;
    }

    public function get()
    {
        if (file_exists($this->cachePath)) {
            $cached = json_decode(file_get_contents($this->cachePath), true);
            if (is_array($cached) && isset($cached['token'], $cached['exp']) && $cached['exp'] > time() + 3600) {
                return $cached['token'];
            }
        }
        $token = $this->generate();
        file_put_contents($this->cachePath, json_encode(array(
            'token' => $token,
            'exp' => time() + $this->ttl,
        )), LOCK_EX);
        return $token;
    }
}
