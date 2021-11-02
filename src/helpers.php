<?php

use Illuminate\Support\Facades\Config;

if (!function_exists('env')) {
    /**
     * @param string $key
     * @return mixed
     */
    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * @param string $key
     * @return mixed
     */
    function config($key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('config_path')) {
    /**
     * Emulates Laravels config_path method
     *
     * @return string
     */
    function config_path($path)
    {
        return $path;
    }
}
