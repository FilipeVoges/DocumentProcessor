<?php


namespace App\Modules\Configuration;


class Configuration
{
    /**
     * Autoload
     */
    public static function load()
    {
        require_once getcwd() . '/vendor/autoload.php';
    }
}