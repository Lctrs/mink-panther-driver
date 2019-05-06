<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Tests;

use Behat\Mink\Tests\Driver\AbstractConfig;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\WebDriverBrowserType;
use Lctrs\MinkPantherDriver\PantherDriver;
use const PHP_OS;
use function getenv;
use function strpos;

final class Config extends AbstractConfig
{
    /**
     * Creates an instance of the config.
     *
     * This is the callable registered as a php variable in the phpunit.xml config file.
     * It could be outside the class but this is convenient.
     */
    public static function getInstance() : self
    {
        return new self();
    }

    /**
     * Creates driver instance.
     */
    public function createDriver() : PantherDriver
    {
        if ($_SERVER['SELENIUM'] ?? false) {
            $browser = $_SERVER['BROWSER_NAME'] ?? WebDriverBrowserType::FIREFOX;

            if ($browser === WebDriverBrowserType::FIREFOX) {
                $desiredCapabilities = DesiredCapabilities::firefox();
            } elseif ($browser === WebDriverBrowserType::CHROME) {
                $options = new ChromeOptions();
                $options->addArguments([
                    '--headless',
                    '--window-size=1200,1100',
                    '--disable-gpu',
                    '--no-sandbox',
                ]);

                $desiredCapabilities = $options->toCapabilities();
            } else {
                $desiredCapabilities = new DesiredCapabilities();
            }

            return PantherDriver::createSeleniumDriver(
                'http://localhost:4444/wd/hub',
                $desiredCapabilities
            );
        }

        return PantherDriver::createChromeDriver();
    }

    /**
     * @inheritdoc
     */
    public function skipMessage($testCase, $test) : ?string
    {
        if ($testCase === 'Behat\Mink\Tests\Driver\Form\Html5Test'
            && $test === 'testHtml5Types'
        ) {
            return 'WebDriver does not support setting value in color inputs. See https://code.google.com/p/selenium/issues/detail?id=7650';
        }

        $headless = ! ($_SERVER['PANTHER_NO_HEADLESS'] ?? false);
        if ($testCase === 'Behat\Mink\Tests\Driver\Js\WindowTest'
            && (strpos($test, 'testWindowMaximize') === 0)
            && (getenv('TRAVIS') === 'true' || $headless)
        ) {
            return 'Maximizing the window does not work when running the browser in Xvfb/Headless.';
        }

        if (PHP_OS === 'Darwin'
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

    protected function supportsCss() : bool
    {
        return true;
    }
}
