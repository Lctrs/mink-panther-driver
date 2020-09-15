<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Test\Integration\Custom;

use Behat\Mink\Tests\Driver\Js\EventsTest as MinkEventsTest;

use function file_get_contents;
use function file_put_contents;

final class EventsTest extends MinkEventsTest
{
    /**
     * @inheritDoc
     * @dataProvider provideKeyboardEventsModifiers
     */
    public function testKeyboardEvents($modifier, $eventProperties): void
    {
        // we use custom html file cause when using keyUp then we cannot release key and modifier at once
        // which will break base test
        file_put_contents(
            __DIR__ . '/../../../vendor/mink/driver-testsuite/web-fixtures/js_test_custom.html',
            file_get_contents(__DIR__ . '/web-fixtures/js_test.html')
        );

        $this->getSession()->visit($this->pathTo('/js_test_custom.html'));
        $webAssert = $this->getAssertSession();

        $input1 = $webAssert->elementExists('css', '.elements input.input.first');
        $input2 = $webAssert->elementExists('css', '.elements input.input.second');
        $input3 = $webAssert->elementExists('css', '.elements input.input.third');
        $event  = $webAssert->elementExists('css', '.elements .text-event');

        $input2->keyPress('r', $modifier);
        if ($modifier === 'shift') {
            self::assertStringContainsString('key pressed:82 / ' . $eventProperties, $event->getText());
        } else {
            self::assertStringContainsString('key pressed:114 / ' . $eventProperties, $event->getText());
        }

        $input1->keyDown('u', $modifier);
        self::assertStringContainsString('key downed:' . $eventProperties, $event->getText());

        // 85 = u
        $input3->keyUp(85, $modifier);
        self::assertStringContainsString('key upped:85 / ' . $eventProperties, $event->getText());
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public function provideKeyboardEventsModifiers(): array
    {
        return [
            'none' => [null, '0 / 0 / 0 / 0'],
            'alt' => ['alt', '1 / 0 / 0 / 0'],
            'ctrl' => ['ctrl', '0 / 1 / 0 / 0'],
            'shift' => ['shift', '0 / 0 / 1 / 0'],
            'meta' => ['meta', '0 / 0 / 0 / 1'],
        ];
    }
}
