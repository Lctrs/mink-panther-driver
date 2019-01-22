<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Tests\Extension\Driver;

use Lctrs\MinkPantherDriver\Extension\Driver\PantherFactory;
use Lctrs\MinkPantherDriver\PantherDriver;
use PHPUnit\Framework\TestCase;

class PantherFactoryTest extends TestCase
{
    /** @var PantherFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory = new PantherFactory();
    }

    public function testItSupportsJavascript() : void
    {
        $this->assertTrue($this->factory->supportsJavascript());
    }

    public function testItBuildsDriver() : void
    {
        $definition = $this->factory->buildDriver([]);

        $this->assertSame(PantherDriver::class, $definition->getClass());
    }
}
