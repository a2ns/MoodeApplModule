<?php
/*
 * moOde Apple Music module - configuration loader
 *
 * Reads module settings from the JSON config file.
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 */

namespace Applemusic;

class Config
{
    private $data;

    public function __construct($path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('Apple Music module is not configured. Run the installer and fill in config.json.');
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Apple Music config file is invalid JSON: ' . json_last_error_msg());
        }
        $this->data = $data;
    }

    public function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function isConfigured()
    {
        return $this->get('team_id') && $this->get('key_id') && $this->get('private_key_path');
    }

    public function storefront()
    {
        return $this->get('storefront', 'FR');
    }

    public function userTokenPath()
    {
        return $this->get('data_dir') . '/user_token.txt';
    }

    public function getUserToken()
    {
        $path = $this->userTokenPath();
        if (!file_exists($path)) {
            return null;
        }
        $token = trim(file_get_contents($path));
        return $token !== '' ? $token : null;
    }

    public function setUserToken($token)
    {
        $path = $this->userTokenPath();
        file_put_contents($path, $token, LOCK_EX);
        chmod($path, 0640);
    }
}
