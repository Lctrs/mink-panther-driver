<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Test\Integration;

use Behat\Mink\Tests\Driver\AbstractConfig;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Lctrs\MinkPantherDriver\PantherDriver;
use Lctrs\MinkPantherDriver\Test\Integration\Custom\EventsTest;
use OndraM\CiDetector\CiDetector;
use PHPUnit\Runner\AfterLastTestHook;

use function assert;
use function is_string;
use function strpos;

use const PHP_OS;

final class Config extends AbstractConfig implements AfterLastTestHook
{
    /**
     * Creates an instance of the config.
     *
     * This is the callable registered as a php variable in the phpunit.xml config file.
     * It could be outside the class but this is convenient.
     */
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * Creates driver instance.
     */
    public function createDriver(): PantherDriver
    {
        $browser = $_SERVER['BROWSER_NAME'] ?? PantherDriver::CHROME;

        assert(is_string($browser));

        if ($browser === PantherDriver::SELENIUM) {
            $options = new ChromeOptions();
            $options->addArguments([
                '--headless',
                '--window-size=1200,1100',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
            ]);

            return new PantherDriver($browser, [
                'host' => (string) $_SERVER['SELENIUM_HOST'],
                'capabilities' => $options->toCapabilities(),
            ]);
        }

        return new PantherDriver($browser);
    }

    /**
     * @inheritdoc
     */
    public function skipMessage($testCase, $test): ?string
    {
        $headless = ! ($_SERVER['PANTHER_NO_HEADLESS'] ?? false);
        if (
            $testCase === 'Behat\Mink\Tests\Driver\Js\WindowTest'
            && (strpos($test, 'testWindowMaximize') === 0)
            && ($headless || (new CiDetector())->isCiDetected())
        ) {
            return 'Maximizing the window does not work when running the browser in Xvfb/Headless.';
        }

        if (
            ($_SERVER['BROWSER_NAME'] ?? PantherDriver::CHROME) === PantherDriver::FIREFOX
            && $testCase === EventsTest::class
            && strpos($test, 'testKeyboardEvents') === 0
        ) {
            return 'Keyboard events tests need some works on Firefox.';
        }

        if (
            $testCase === EventsTest::class
            && strpos($test, 'testDoubleClick') === 0
        ) {
            // https://github.com/w3c/webdriver/issues/1197
            return "Double clicks aren't detected as dblclick events anymore in W3C mode";
        }

        if (
            PHP_OS === 'Darwin'
            && $testCase === 'Behat\Mink\Tests\Driver\Js\EventsTest'
            && strpos($test, 'testKeyboardEvents') === 0
        ) {
            // https://bugs.chromium.org/p/chromium/issues/detail?id=13891#c16
            // Control + <char> will not trigger keypress
            // Option + <char> will output different results "special char" ©
            return 'MacOS does not behave same as Windows or Linux';
        }

        return parent::skipMessage($testCase, $test);
    }

    /**
     * @return true
     */
    protected function supportsCss(): bool
    {
        return true;
    }

    public function executeAfterLastTest(): void
    {
        PantherDriver::stopWebServer();
    }
}
