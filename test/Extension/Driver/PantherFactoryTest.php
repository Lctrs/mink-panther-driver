<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Test\Extension\Driver;

use Behat\Mink\Driver\DriverInterface;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverCapabilities;
use Lctrs\MinkPantherDriver\Extension\Driver\PantherFactory;
use Lctrs\MinkPantherDriver\PantherDriver;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use function assert;

final class PantherFactoryTest extends AbstractExtensionTestCase
{
    use ConfigurationTestCaseTrait;

    /** @var PantherFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory = new PantherFactory();

        parent::setUp();
    }

    public function testDriverName() : void
    {
        self::assertSame('panther', $this->factory->getDriverName());
    }

    public function testItSupportsJavascript() : void
    {
        self::assertTrue($this->factory->supportsJavascript());
    }

    /**
     * @param mixed[] $config
     * @param mixed[] $expected
     *
     * @dataProvider validConfigurationProvider
     */
    public function testValidConfigurations(array $config, array $expected) : void
    {
        $this->assertProcessedConfigurationEquals($config, $expected);
    }

    /**
     * @param mixed[] $config
     *
     * @dataProvider invalidConfigurationProvider
     */
    public function testInvalidConfigurations(array $config, string $expectedMessage) : void
    {
        $this->assertConfigurationIsInvalid($config, $expectedMessage);
    }

    public function testItBuildsChromeDriver() : void
    {
        $this->load([
            'chrome' => [
                'binary' => '/usr/lib/chromium/chromedriver',
                'arguments' => ['--no-sandbox'],
                'options' => [
                    'scheme' => 'http',
                    'host' => '127.0.0.1',
                    'port' => 9515,
                    'path' => '/status',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService(DriverInterface::class, PantherDriver::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(DriverInterface::class, 0, '/usr/lib/chromium/chromedriver');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(DriverInterface::class, 1, ['--no-sandbox']);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(DriverInterface::class, 2, [
            'scheme' => 'http',
            'host' => '127.0.0.1',
            'port' => 9515,
            'path' => '/status',
        ]);

        $definition = $this->container->getDefinition(DriverInterface::class);
        self::assertSame([PantherDriver::class, 'createChromeDriver'], $definition->getFactory());
    }

    public function testItBuildsSeleniumDriver() : void
    {
        $this->load([
            'selenium' => [
                'host' => 'http://127.0.0.1:4444/wd/hub',
                'browser' => 'firefox',
            ],
        ]);

        $this->assertContainerBuilderHasService(DriverInterface::class, PantherDriver::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(DriverInterface::class, 0, 'http://127.0.0.1:4444/wd/hub');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            DriverInterface::class,
            1,
            (new Definition(WebDriverCapabilities::class))
                ->setFactory([DesiredCapabilities::class, 'firefox'])
        );

        $definition = $this->container->getDefinition(DriverInterface::class);
        self::assertSame([PantherDriver::class, 'createSeleniumDriver'], $definition->getFactory());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to build a Lctrs\MinkPantherDriver\PantherDriver instance with the given config.
     */
    public function testItThrowsExceptionWhenBuildingDriverWithInvalidConfiguration() : void
    {
        $this->factory->buildDriver([
            'invalid' => [],
        ]);
    }

    /**
     * @return iterable|mixed[]
     */
    public function validConfigurationProvider() : iterable
    {
        yield [
            [[]],
            [
                'chrome' => [
                    'binary' => null,
                    'arguments' => null,
                    'options' => [],
                ],
            ],
        ];

        yield [
            [['chrome' => null]],
            [
                'chrome' => [
                    'binary' => null,
                    'arguments' => null,
                    'options' => [],
                ],
            ],
        ];

        yield [
            [
                [
                    'chrome' => [
                        'binary' => null,
                        'arguments' => [],
                        'options' => [],
                    ],
                ],
            ],
            [
                'chrome' => [
                    'binary' => null,
                    'arguments' => [],
                    'options' => [],
                ],
            ],
        ];

        yield [
            [
                [
                    'chrome' => [
                        'binary' => null,
                        'arguments' => null,
                    ],
                ],
            ],
            [
                'chrome' => [
                    'binary' => null,
                    'arguments' => null,
                    'options' => [],
                ],
            ],
        ];

        yield [
            [
                [
                    'chrome' => [
                        'options' => ['scheme' => 'https'],
                    ],
                ],
            ],
            [
                'chrome' => [
                    'binary' => null,
                    'arguments' => null,
                    'options' => ['scheme' => 'https'],
                ],
            ],
        ];

        yield [
            [
                [
                    'chrome' => [
                        'binary' => '/usr/lib/chromium/chromedriver',
                        'arguments' => ['--no-sandbox'],
                        'options' => [
                            'scheme' => 'http',
                            'host' => '127.0.0.1',
                            'port' => 9515,
                            'path' => '/status',
                        ],
                    ],
                ],
            ],
            [
                'chrome' => [
                    'binary' => '/usr/lib/chromium/chromedriver',
                    'arguments' => ['--no-sandbox'],
                    'options' => [
                        'scheme' => 'http',
                        'host' => '127.0.0.1',
                        'port' => 9515,
                        'path' => '/status',
                    ],
                ],
            ],
        ];

        yield [
            [['selenium' => null]],
            [
                'selenium' => [
                    'host' => null,
                    'browser' => 'chrome',
                ],
            ],
        ];

        yield [
            [
                [
                    'selenium' => [
                        'host' => 'http://127.0.0.1:4444/wd/hub',
                        'browser' => 'firefox',
                    ],
                ],
            ],
            [
                'selenium' => [
                    'host' => 'http://127.0.0.1:4444/wd/hub',
                    'browser' => 'firefox',
                ],
            ],
        ];
    }

    /**
     * @return iterable|mixed[]
     */
    public function invalidConfigurationProvider() : iterable
    {
        yield [
            [
                [
                    'chrome' => ['arguments' => '--test'],
                ],
            ],
            '"arguments" must be an array of strings or null.',
        ];

        yield [
            [
                [
                    'chrome' => ['arguments' => ['--no-sandbox', 1]],
                ],
            ],
            '"arguments" must be an array of strings or null.',
        ];

        yield [
            [
                [
                    'selenium' => ['browser' => 'invalid-browser'],
                ],
            ],
            '"invalid-browser" is not a valid or supported browser.',
        ];

        yield [
            [
                [
                    'selenium' => ['browser' => 1],
                ],
            ],
            '1 is not a valid or supported browser.',
        ];
    }

    protected function getConfiguration() : ConfigurationInterface
    {
        return new class($this->factory) implements ConfigurationInterface
        {
            /** @var PantherFactory */
            private $factory;

            public function __construct(PantherFactory $factory)
            {
                $this->factory = $factory;
            }

            public function getConfigTreeBuilder() : TreeBuilder
            {
                $treeBuilder = new TreeBuilder('panther');
                $rootNode    = $treeBuilder->getRootNode();

                assert($rootNode instanceof ArrayNodeDefinition);

                $this->factory->configure($rootNode);

                return $treeBuilder;
            }
        };
    }

    /**
     * Return an array of container extensions you need to be registered for each test (usually just the container
     * extension you are testing.
     *
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions() : array
    {
        $extension = new class($this->getConfiguration(), $this->factory) extends Extension
        {
            /** @var ConfigurationInterface */
            private $configuration;

            /** @var PantherFactory */
            private $factory;

            public function __construct(ConfigurationInterface $configuration, PantherFactory $factory)
            {
                $this->configuration = $configuration;
                $this->factory       = $factory;
            }

            /**
             * @param mixed[] $configs
             */
            public function load(array $configs, ContainerBuilder $container) : void
            {
                $configs = $this->processConfiguration($this->configuration, $configs);

                $container->setDefinition(DriverInterface::class, $this->factory->buildDriver($configs));
            }

            public function getAlias() : string
            {
                return 'panther';
            }
        };

        return [$extension];
    }
}
