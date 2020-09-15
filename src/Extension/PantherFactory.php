<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Extension;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverCapabilities;
use Lctrs\MinkPantherDriver\PantherDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

use function is_string;
use function method_exists;

/**
 * @internal
 */
final class PantherFactory implements DriverFactory
{
    public function getDriverName(): string
    {
        return 'panther';
    }

    public function supportsJavascript(): bool
    {
        return true;
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder
            ->children()
                ->enumNode('driver')
                    ->values([PantherDriver::CHROME, PantherDriver::FIREFOX, PantherDriver::SELENIUM])
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('hostname')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('selenium')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')->defaultNull()->end()
                        ->scalarNode('browser')
                            ->defaultValue('chrome')
                            ->validate()
                                ->ifTrue(
                                    /**
                                     * @param string|bool|float|int $v
                                     */
                                    static function ($v): bool {
                                        if (! is_string($v)) {
                                            return true;
                                        }

                                        return ! method_exists(DesiredCapabilities::class, $v);
                                    }
                                )
                                ->thenInvalid('%s is not a valid or supported browser.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array{driver: string, options: array{hostname: string|null}, selenium: array{host: string|null, browser: string}} $config
     */
    public function buildDriver(array $config): Definition
    {
        if ($config['driver'] === PantherDriver::SELENIUM) {
            $config['selenium']['capabilities'] = (new Definition(WebDriverCapabilities::class))
                ->setFactory([DesiredCapabilities::class, $config['selenium']['browser']]);

            unset($config['selenium']['browser']);

            return new Definition(
                PantherDriver::class,
                [
                    $config['driver'],
                    $config['selenium'],
                ]
            );
        }

        return new Definition(
            PantherDriver::class,
            [
                $config['driver'],
                $config['options'],
            ]
        );
    }
}
