<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Tests\Extension;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Lctrs\MinkPantherDriver\Extension\Driver\PantherFactory;
use Lctrs\MinkPantherDriver\Extension\PantherExtension;
use PHPUnit\Framework\TestCase;

class PantherExtensionTest extends TestCase
{
    /** @var PantherExtension */
    private $extension;

    protected function setUp() : void
    {
        $this->extension = new PantherExtension();
    }

    public function testConfigKey() : void
    {
        $this->assertSame('panther', $this->extension->getConfigKey());
    }

    public function testItRegistersMinkDriver() : void
    {
        $minkExtension = $this->createMock(MinkExtension::class);
        $minkExtension->expects($this->once())->method('getConfigKey')->willReturn('mink');
        $extensionManager = new ExtensionManager([$minkExtension]);

        $minkExtension->expects($this->once())->method('registerDriverFactory')
            ->with($this->callback(static function ($factory) {
                return $factory instanceof PantherFactory;
            }));
        $this->extension->initialize($extensionManager);
    }
}
