<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Test\Integration;

use Behat\Mink\Tests\Driver\AbstractConfig;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Lctrs\MinkPantherDriver\PantherDriver;
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
                '--remote-debugging-port=9222',
                '--disable-infobars',
                '--disable-extensions',
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

    protected function supportsCss(): bool
    {
        return true;
    }

    public function executeAfterLastTest(): void
    {
        PantherDriver::stopWebServer();
    }
}
