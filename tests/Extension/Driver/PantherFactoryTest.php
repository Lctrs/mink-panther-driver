<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Tests\Extension\Driver;

use Lctrs\MinkPantherDriver\Extension\Driver\PantherFactory;
use Lctrs\MinkPantherDriver\PantherDriver;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function method_exists;

class PantherFactoryTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /** @var PantherFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory = new PantherFactory();
    }

    public function testDriverName() : void
    {
        $this->assertSame('panther', $this->factory->getDriverName());
    }

    public function testItSupportsJavascript() : void
    {
        $this->assertTrue($this->factory->supportsJavascript());
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
        $definition = $this->factory->buildDriver([
            'chrome' => [
                'binary' => null,
                'arguments' => null,
                'options' => [],
            ],
        ]);

        $this->assertSame([PantherDriver::class, 'createChromeDriver'], $definition->getFactory());
        $this->assertCount(3, $definition->getArguments());
        $this->assertNull($definition->getArgument(0));
        $this->assertNull($definition->getArgument(1));
        $this->assertSame([], $definition->getArgument(2));

        $definition = $this->factory->buildDriver([
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

        $this->assertSame([PantherDriver::class, 'createChromeDriver'], $definition->getFactory());
        $this->assertCount(3, $definition->getArguments());
        $this->assertSame('/usr/lib/chromium/chromedriver', $definition->getArgument(0));
        $this->assertSame(['--no-sandbox'], $definition->getArgument(1));
        $this->assertSame([
            'scheme' => 'http',
            'host' => '127.0.0.1',
            'port' => 9515,
            'path' => '/status',
        ], $definition->getArgument(2));
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
    }

    /**
     * @inheritDoc
     */
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

            /**
             * @inheritDoc
             */
            public function getConfigTreeBuilder() : TreeBuilder
            {
                $treeBuilder = new TreeBuilder('panther');

                if (method_exists($treeBuilder, 'getRootNode')) {
                    $rootNode = $treeBuilder->getRootNode();
                } else {
                    // BC layer for symfony/config 4.1 and older
                    $rootNode = $treeBuilder->root('panther');
                }

                $this->factory->configure($rootNode);

                return $treeBuilder;
            }
        };
    }
}
