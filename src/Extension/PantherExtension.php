<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Extension;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class PantherExtension implements Extension
{
    public function process(ContainerBuilder $container) : void
    {
    }

    public function getConfigKey() : string
    {
        return 'panther';
    }

    public function initialize(ExtensionManager $extensionManager) : void
    {
        $minkExtension = $extensionManager->getExtension('mink');

        if (! $minkExtension instanceof MinkExtension) {
            return;
        }

        $minkExtension->registerDriverFactory(new PantherFactory());
    }

    public function configure(ArrayNodeDefinition $builder) : void
    {
    }

    /**
     * @param mixed[] $config
     */
    public function load(ContainerBuilder $container, array $config) : void
    {
    }
}
