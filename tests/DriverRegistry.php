<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Tests;

use Lctrs\MinkPantherDriver\PantherDriver;

final class DriverRegistry
{
    /** @var PantherDriver[] */
    private static $drivers = [];

    public static function register(PantherDriver $driver) : PantherDriver
    {
        return self::$drivers[] = $driver;
    }

    public static function stop() : void
    {
        foreach (self::$drivers as $driver) {
            $driver->stop();
        }

        self::$drivers = [];
    }
}
