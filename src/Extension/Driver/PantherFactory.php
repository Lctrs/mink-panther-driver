<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Extension\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use Lctrs\MinkPantherDriver\PantherDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use function is_array;
use function is_string;

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
        $builder
            ->children()
                ->arrayNode('chrome')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('binary')->defaultNull()->end()
                        ->variableNode('arguments')
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(static function ($v) {
                                    if ($v === null) {
                                        return false;
                                    }

                                    if (! is_array($v)) {
                                        return true;
                                    }

                                    foreach ($v as $child) {
                                        if (! is_string($child)) {
                                            return true;
                                        }
                                    }

                                    return false;
                                })
                                ->thenInvalid('"arguments" must be an array of strings or null.')
                            ->end()
                        ->end()
                        ->arrayNode('options')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('scheme')->end()
                                ->scalarNode('host')->end()
                                ->integerNode('port')->end()
                                ->scalarNode('path')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @inheritdoc
     */
    public function buildDriver(array $config) : Definition
    {
        return (new Definition(PantherDriver::class))
            ->setFactory([PantherDriver::class, 'createChromeDriver'])
            ->setArguments([
                $config['chrome']['binary'],
                $config['chrome']['arguments'],
                $config['chrome']['options'],
            ]);
    }
}
