<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Test\Unit\Extension;

use Behat\Mink\Driver\DriverInterface;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverCapabilities;
use Lctrs\MinkPantherDriver\Extension\PantherFactory;
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

/**
 * @covers \Lctrs\MinkPantherDriver\Extension\PantherFactory
 */
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

    public function testItBuildsDriver() : void
    {
        $this->load(['driver' => 'chrome']);

        $this->assertContainerBuilderHasService(DriverInterface::class, PantherDriver::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(DriverInterface::class, 0, 'chrome');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(DriverInterface::class, 1, ['hostname' => null]);
    }

    public function testItBuildsSeleniumDriver() : void
    {
        $this->load([
            'driver' => 'selenium',
            'selenium' => [
                'host' => 'http://127.0.0.1:4444/wd/hub',
                'browser' => 'firefox',
            ],
        ]);

        $this->assertContainerBuilderHasService(DriverInterface::class, PantherDriver::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(DriverInterface::class, 0, 'selenium');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            DriverInterface::class,
            1,
            [
                'host' => 'http://127.0.0.1:4444/wd/hub',
                'capabilities' => (new Definition(WebDriverCapabilities::class))
                    ->setFactory([DesiredCapabilities::class, 'firefox']),
            ]
        );
    }

    /**
     * @return iterable|mixed[]
     */
    public function validConfigurationProvider() : iterable
    {
        yield [
            [
                ['driver' => 'chrome'],
            ],
            [
                'driver' => 'chrome',
                'options' => ['hostname' => null],
                'selenium' => [
                    'host' => null,
                    'browser' => 'chrome',
                ],
            ],
        ];

        yield [
            [
                ['driver' => 'firefox'],
            ],
            [
                'driver' => 'firefox',
                'options' => ['hostname' => null],
                'selenium' => [
                    'host' => null,
                    'browser' => 'chrome',
                ],
            ],
        ];

        yield [
            [
                ['driver' => 'selenium'],
            ],
            [
                'driver' => 'selenium',
                'options' => ['hostname' => null],
                'selenium' => [
                    'host' => null,
                    'browser' => 'chrome',
                ],
            ],
        ];

        yield [
            [
                [
                    'driver' => 'chrome',
                    'options' => ['hostname' => '127.0.0.1'],
                ],
            ],
            [
                'driver' => 'chrome',
                'options' => ['hostname' => '127.0.0.1'],
                'selenium' => [
                    'host' => null,
                    'browser' => 'chrome',
                ],
            ],
        ];

        yield [
            [
                [
                    'driver' => 'selenium',
                    'selenium' => [
                        'host' => 'http://127.0.0.1:4444/wd/hub',
                        'browser' => 'firefox',
                    ],
                ],
            ],
            [
                'driver' => 'selenium',
                'options' => ['hostname' => null],
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
            [[]],
            'The child node "driver" at path "panther" must be configured.',
        ];

        yield [
            [
                ['driver' => 'invalid-driver'],
            ],
            'The value "invalid-driver" is not allowed for path "panther.driver". Permissible values: "chrome", "firefox", "selenium"',
        ];

        yield [
            [
                [
                    'driver' => 'selenium',
                    'selenium' => ['browser' => 'invalid-browser'],
                ],
            ],
            '"invalid-browser" is not a valid or supported browser.',
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
