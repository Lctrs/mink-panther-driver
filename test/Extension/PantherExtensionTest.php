<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Test\Extension;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Lctrs\MinkPantherDriver\Extension\Driver\PantherFactory;
use Lctrs\MinkPantherDriver\Extension\PantherExtension;
use PHPUnit\Framework\TestCase;

final class PantherExtensionTest extends TestCase
{
    /** @var PantherExtension */
    private $extension;

    protected function setUp() : void
    {
        $this->extension = new PantherExtension();
    }

    public function testConfigKey() : void
    {
        self::assertSame('panther', $this->extension->getConfigKey());
    }

    public function testItRegistersMinkDriver() : void
    {
        $minkExtension = $this->createMock(MinkExtension::class);
        $minkExtension->expects(self::once())->method('getConfigKey')->willReturn('mink');
        $extensionManager = new ExtensionManager([$minkExtension]);

        $minkExtension->expects(self::once())->method('registerDriverFactory')
            ->with(self::callback(static function ($factory) : bool {
                return $factory instanceof PantherFactory;
            }));
        $this->extension->initialize($extensionManager);
    }

    public function testItDoesNotRegisterMinkDriverWhenMinkExtensionIsNotPresent() : void
    {
        $extensionManager = new ExtensionManager([]);

        $minkExtension = $this->createMock(MinkExtension::class);
        $minkExtension->expects(self::never())->method('registerDriverFactory');

        $this->extension->initialize($extensionManager);
    }
}
