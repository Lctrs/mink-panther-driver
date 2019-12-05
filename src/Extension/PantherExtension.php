<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Extension;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Lctrs\MinkPantherDriver\Extension\Driver\PantherFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PantherExtension implements Extension
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container) : void
    {
    }

    /**
     * @inheritdoc
     */
    public function getConfigKey() : string
    {
        return 'panther';
    }

    /**
     * @inheritdoc
     */
    public function initialize(ExtensionManager $extensionManager) : void
    {
        /** @var MinkExtension|null $minkExtension */
        $minkExtension = $extensionManager->getExtension('mink');

        if ($minkExtension === null) {
            return;
        }

        $minkExtension->registerDriverFactory(new PantherFactory());
    }

    /**
     * @inheritdoc
     */
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
