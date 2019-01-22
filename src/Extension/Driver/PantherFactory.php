<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Extension\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use Lctrs\MinkPantherDriver\PantherDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

final class PantherFactory implements DriverFactory
{
    /**
     * @inheritdoc
     */
    public function getDriverName() : string
    {
        return 'panther';
    }

    /**
     * @inheritdoc
     */
    public function supportsJavascript() : bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function configure(ArrayNodeDefinition $builder) : void
    {
    }

    /**
     * @inheritdoc
     */
    public function buildDriver(array $config) : Definition
    {
        return new Definition(PantherDriver::class);
    }
}
