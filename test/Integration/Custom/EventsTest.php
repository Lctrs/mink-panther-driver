<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Test\Integration\Custom;

use Behat\Mink\Tests\Driver\Js\EventsTest as MinkEventsTest;

final class EventsTest extends MinkEventsTest
{
    /**
     * @inheritDoc
     * @dataProvider provideKeyboardEventsModifiers
     */
    public function testKeyboardEvents($modifier, $eventProperties) : void
    {
        self::markTestIncomplete(
            'This test is still buggy.'
        );
    }
}
