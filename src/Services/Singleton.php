<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

class Singleton
{
    private static object $instance;

    private final function  __construct()
    {
        // noop
    }

    public static function getInstance(): object
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
