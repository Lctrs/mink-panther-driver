<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverCapabilities;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverHasInputDevices;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
use Lctrs\MinkPantherDriver\Bridge\Facebook\WebDriver\WebDriverRadios;
use RuntimeException;
use Symfony\Component\Panther\Client;
use const PHP_EOL;
use function array_map;
use function array_merge;
use function chr;
use function in_array;
use function is_int;
use function is_string;
use function ord;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function trim;
use function urldecode;
use function urlencode;

final class PantherDriver extends CoreDriver
{
    /** @var Client */
    private $client;
    /** @var bool */
    private $isStarted = false;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string[]|null $arguments
     * @param mixed[]       $options
     */
    public static function createChromeDriver(
        ?string $chromeDriverBinary = null,
        ?array $arguments = null,
        array $options = []
    ) : self {
        return new self(Client::createChromeClient($chromeDriverBinary, $arguments, $options));
    }

    public static function createSeleniumDriver(
        ?string $host = null,
        ?WebDriverCapabilities $capabilities = null
    ) : self {
        return new self(Client::createSeleniumClient($host, $capabilities));
    }

    /**
     * @inheritdoc
     */
    public function start() : void
    {
        $this->client->start();

        $this->isStarted = true;
    }

    /**
     * @inheritdoc
     */
    public function isStarted() : bool
    {
        return $this->isStarted;
    }

    /**
     * @inheritdoc
     */
    public function stop() : void
    {
        $this->isStarted = false;

        $this->client->quit();
    }

    /**
     * @inheritdoc
     */
    public function reset() : void
    {
        if (! $this->isStarted()) {
            return;
        }

        $this->client->manage()->deleteAllCookies();
    }

    /**
     * @inheritdoc
     */
    public function visit($url) : void
    {
        $this->client->get($url);
    }

    /**
     * @inheritdoc
     */
    public function getCurrentUrl() : string
    {
        return $this->client->getCurrentURL();
    }

    /**
     * @inheritdoc
     */
    public function reload() : void
    {
        $this->client->reload();
    }

    /**
     * @inheritdoc
     */
    public function forward() : void
    {
        $this->client->forward();
    }

    /**
     * @inheritdoc
     */
    public function back() : void
    {
        $this->client->back();
    }

    /**
     * @inheritdoc
     */
    public function switchToWindow($name = null) : void
    {
        $this->client->switchTo()->window($name ?? '');
    }

    /**
     * @inheritdoc
     */
    public function switchToIFrame($name = null) : void
    {
        if ($name === null) {
            $this->client->switchTo()->defaultContent();

            return;
        }

        $this->client->switchTo()->frame($name);
    }

    /**
     * @inheritdoc
     */
    public function setCookie($name, $value = null) : void
    {
        $manager = $this->client->manage();

        if ($value === null) {
            $manager->deleteCookieNamed($name);

            return;
        }

        $manager->addCookie(new Cookie($name, urlencode((string) $value)));
    }

    /**
     * @inheritdoc
     */
    public function getCookie($name) : ?string
    {
        $cookie = $this->client->manage()->getCookieNamed($name);

        if ($cookie === null) {
            return null;
        }

        return urldecode($cookie->getValue());
    }

    /**
     * @inheritdoc
     */
    public function getContent() : string
    {
        return str_replace(
            ["\r", "\r\n", "\n"],
            PHP_EOL,
            $this->client->getPageSource()
        );
    }

    /**
     * @inheritdoc
     */
    public function getScreenshot() : string
    {
        return $this->client->takeScreenshot();
    }

    /**
     * @inheritdoc
     */
    public function getWindowNames() : array
    {
        return $this->client->getWindowHandles();
    }

    /**
     * @inheritdoc
     */
    public function getWindowName() : string
    {
        return $this->client->getWindowHandle();
    }

    /**
     * @inheritdoc
     */
    protected function findElementXpaths($xpath) : array
    {
        $elements = $this->client->findElements(WebDriverBy::xpath($xpath));

        $xPaths = [];
        foreach ($elements as $key => $element) {
            $xPaths[] = sprintf('(%s)[%d]', $xpath, $key+1);
        }

        return $xPaths;
    }

    /**
     * @inheritdoc
     */
    public function getTagName($xpath) : string
    {
        return $this->findElementOrThrow($xpath)->getTagName();
    }

    /**
     * @inheritdoc
     */
    public function getText($xpath) : string
    {
        return str_replace(
            ["\r", "\r\n", "\n"],
            ' ',
            $this->findElementOrThrow($xpath)->getText()
        );
    }

    /**
     * @inheritdoc
     */
    public function getHtml($xpath) : string
    {
        return $this->executeScriptOn($this->findElementOrThrow($xpath), 'return arguments[0].innerHTML;');
    }

    /**
     * @inheritdoc
     */
    public function getOuterHtml($xpath) : string
    {
        return $this->executeScriptOn($this->findElementOrThrow($xpath), 'return arguments[0].outerHTML;');
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($xpath, $name) : ?string
    {
        $element = $this->findElementOrThrow($xpath);

        /**
         * If attribute is present but does not have value, it's considered as Boolean Attributes https://html.spec.whatwg.org/#boolean-attributes
         * but here result may be unexpected in case of <element my-attr/>, my-attr should return TRUE, but it will return "empty string"
         *
         * @see https://w3c.github.io/webdriver/#get-element-attribute
         */
        if (! $this->hasAttribute($element, $name)) {
            return null;
        }

        return $element->getAttribute($name);
    }

    /**
     * @throws UnsupportedDriverActionException
     */
    private function hasAttribute(WebDriverElement $element, string $name) : bool
    {
        return $this->executeScriptOn($element, 'return arguments[0].hasAttribute(arguments[1]);', $name);
    }

    /**
     * @inheritdoc
     */
    public function getValue($xpath)
    {
        $element = $this->findElementOrThrow($xpath);
        $tagName = $element->getTagName();

        if ($tagName === 'input') {
            $type = strtolower($element->getAttribute('type'));

            if ($type === 'checkbox') {
                return $element->isSelected() ? $element->getAttribute('value') : null;
            }

            if ($type === 'radio') {
                $radio = new WebDriverRadios($element);

                try {
                    return $radio->getFirstSelectedOption()->getAttribute('value');
                } catch (NoSuchElementException $e) {
                    return null;
                }
            }
        }

        if ($tagName === 'select') {
            $select = new WebDriverSelect($element);

            if (! $select->isMultiple()) {
                try {
                    return $select->getFirstSelectedOption()->getAttribute('value');
                } catch (NoSuchElementException $e) {
                    return null;
                }
            }

            return array_map(static function (WebDriverElement $element) : string {
                return $element->getAttribute('value');
            }, $select->getAllSelectedOptions());
        }

        return $element->getAttribute('value');
    }

    /**
     * @inheritdoc
     */
    public function setValue($xpath, $value) : void
    {
        $element = $this->findElementOrThrow($xpath);
        $tagName = $element->getTagName();

        if ($tagName === 'select') {
            $select = new WebDriverSelect($element);

            if ($select->isMultiple()) {
                $select->deselectAll();
            }

            foreach ((array) $value as $val) {
                $select->selectByValue($val);
            }

            return;
        }

        if ($tagName === 'input') {
            $type = strtolower($element->getAttribute('type'));

            if (in_array($type, ['submit', 'image', 'button', 'reset'])) {
                throw new DriverException(sprintf('Impossible to set value an element with XPath "%s" as it is not a select, textarea or textbox', $xpath));
            }

            if ($type === 'checkbox') {
                if ($element->isSelected() xor (bool) $value) {
                    $element->click();
                }

                return;
            }

            if ($type === 'radio') {
                try {
                    (new WebDriverRadios($element))->selectByValue($value);
                } catch (WebDriverException $e) {
                    throw new DriverException($e->getMessage(), 0, $e);
                }

                return;
            }

            if ($type === 'file') {
                $this->attachFileTo($element, $value);

                return;
            }
        }

        $value = (string) $value;

        if (in_array($tagName, ['input', 'textarea'])) {
            $existingValueLength = strlen($element->getAttribute('value'));
            // Add the TAB key to ensure we unfocus the field as browsers are triggering the change event only
            // after leaving the field.
            $value = str_repeat(WebDriverKeys::BACKSPACE . WebDriverKeys::DELETE, $existingValueLength) . $value;
        }

        $element->sendKeys($value);
        // Remove the focus from the element if the field still has focus in
        // order to trigger the change event. By doing this instead of simply
        // triggering the change event for the given xpath we ensure that the
        // change event will not be triggered twice for the same element if it
        // has lost focus in the meanwhile. If the element has lost focus
        // already then there is nothing to do as this will already have caused
        // the triggering of the change event for that element.
        $element->sendKeys(WebDriverKeys::TAB);
    }

    /**
     * @inheritdoc
     */
    public function check($xpath) : void
    {
        $element = $this->findElementOrThrow($xpath);

        $this->ensureInputType($element, $xpath, 'checkbox', 'check');

        if ($element->isSelected()) {
            return;
        }

        $element->click();
    }

    /**
     * @inheritdoc
     */
    public function uncheck($xpath) : void
    {
        $element = $this->findElementOrThrow($xpath);

        $this->ensureInputType($element, $xpath, 'checkbox', 'uncheck');

        if (! $element->isSelected()) {
            return;
        }

        $element->click();
    }

    /**
     * @inheritdoc
     */
    public function isChecked($xpath) : bool
    {
        return $this->isSelected($xpath);
    }

    /**
     * @inheritdoc
     */
    public function selectOption($xpath, $value, $multiple = false) : void
    {
        $element = $this->findElementOrThrow($xpath);
        $tagName = $element->getTagName();

        if ($tagName === 'input' && strtolower($element->getAttribute('type')) === 'radio') {
            try {
                (new WebDriverRadios($element))->selectByValue($value);
            } catch (WebDriverException $e) {
                throw new DriverException($e->getMessage(), 0, $e);
            }

            return;
        }

        if ($tagName === 'select') {
            try {
                $select = new WebDriverSelect($element);

                if (! $multiple && $select->isMultiple()) {
                    $select->deselectAll();
                }

                try {
                    $select->selectByValue($value);
                } catch (NoSuchElementException $e) {
                    $select->selectByVisibleText($value);
                }
            } catch (WebDriverException $e) {
                throw new DriverException($e->getMessage(), 0, $e);
            }

            return;
        }

        throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
    }

    /**
     * @inheritdoc
     */
    public function isSelected($xpath) : bool
    {
        return $this->findElementOrThrow($xpath)->isSelected();
    }

    /**
     * @inheritdoc
     */
    public function click($xpath) : void
    {
        $this->findElementOrThrow($xpath)->click();
    }

    /**
     * @inheritdoc
     */
    public function doubleClick($xpath) : void
    {
        $this->createWebDriverAction()->doubleClick(
            $this->findElementOrThrow($xpath)
        )->perform();
    }

    /**
     * @inheritdoc
     */
    public function rightClick($xpath) : void
    {
        $this->createWebDriverAction()->contextClick(
            $this->findElementOrThrow($xpath)
        )->perform();
    }

    /**
     * @inheritdoc
     */
    public function attachFile($xpath, $path) : void
    {
        $fileInput = $this->findElementOrThrow($xpath);
        $this->ensureInputType($fileInput, $xpath, 'file', 'attach a file on');

        $this->attachFileTo($fileInput, $path);
    }

    /**
     * @inheritdoc
     */
    public function isVisible($xpath) : bool
    {
        return $this->findElementOrThrow($xpath)->isDisplayed();
    }

    /**
     * @inheritdoc
     */
    public function mouseOver($xpath) : void
    {
        $this->createWebDriverAction()->moveToElement(
            $this->findElementOrThrow($xpath)
        )->perform();
    }

    /**
     * @inheritdoc
     */
    public function focus($xpath) : void
    {
        $element = $this->findElementOrThrow($xpath);

        if ($element->getTagName() === 'input') {
            $element->sendKeys('');

            return;
        }

        $this->createWebDriverAction()->moveToElement($element)->perform();
    }

    /**
     * @inheritdoc
     */
    public function blur($xpath) : void
    {
        $this->findElementOrThrow($xpath)->sendKeys(WebDriverKeys::TAB);
    }

    /**
     * @param string|null $modifier
     *
     * @inheritdoc
     */
    public function keyPress($xpath, $char, $modifier = null) : void
    {
        $this->dispatchKeyboardEventOn($xpath, 'keypress', $char, $modifier);
    }

    /**
     * @param string|null $modifier
     *
     * @inheritdoc
     */
    public function keyDown($xpath, $char, $modifier = null) : void
    {
        $this->dispatchKeyboardEventOn($xpath, 'keydown', $char, $modifier);
    }

    /**
     * @param string|null $modifier
     *
     * @inheritdoc
     */
    public function keyUp($xpath, $char, $modifier = null) : void
    {
        $this->dispatchKeyboardEventOn($xpath, 'keyup', $char, $modifier);
    }

    /**
     * @inheritdoc
     */
    public function dragTo($sourceXpath, $destinationXpath) : void
    {
        $this->createWebDriverAction()->dragAndDrop(
            $this->findElementOrThrow($sourceXpath),
            $this->findElementOrThrow($destinationXpath)
        )->perform();
    }

    /**
     * @inheritdoc
     */
    public function executeScript($script) : void
    {
        if (! $this->client instanceof JavaScriptExecutor) {
            throw new UnsupportedDriverActionException('JS is not supported by %s.', $this);
        }

        if (preg_match('/^function[\s\(]/', $script)) {
            $script = preg_replace('/;$/', '', $script);
            $script = '(' . $script . ')';
        }

        try {
            $this->client->executeScript($script);
        } catch (WebDriverException $e) {
            throw new DriverException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function evaluateScript($script)
    {
        if (! $this->client instanceof JavaScriptExecutor) {
            throw new UnsupportedDriverActionException('JS is not supported by %s.', $this);
        }

        if (strpos(trim($script), 'return ') !== 0) {
            $script = 'return ' . $script;
        }

        try {
            return $this->client->executeScript($script);
        } catch (WebDriverException $e) {
            throw new DriverException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function wait($timeout, $condition) : bool
    {
        $seconds = $timeout / 1000.0;
        $wait    = $this->client->wait($seconds);

        if (is_string($condition)) {
            $script    = 'return ' . $condition . ';';
            $condition = static function (JavaScriptExecutor $driver) use ($script) {
                return $driver->executeScript($script);
            };
        }

        try {
            return (bool) $wait->until($condition);
        } catch (TimeOutException $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function resizeWindow($width, $height, $name = null) : void
    {
        if ($name !== null) {
            throw new UnsupportedDriverActionException('Named windows are not supported yet', $this);
        }

        $this->client
            ->manage()
            ->window()
            ->setSize(new WebDriverDimension($width, $height));
    }

    /**
     * @inheritdoc
     */
    public function maximizeWindow($name = null) : void
    {
        if ($name !== null) {
            throw new UnsupportedDriverActionException('Named windows are not supported yet', $this);
        }

        $this->client
            ->manage()
            ->window()
            ->maximize();
    }

    /**
     * @inheritdoc
     */
    public function submitForm($xpath) : void
    {
        $this->findElementOrThrow($xpath)->submit();
    }

    /**
     * @throws DriverException
     */
    private function findElementOrThrow(string $xpath) : WebDriverElement
    {
        try {
            return $this->client->findElement(WebDriverBy::xpath($xpath));
        } catch (WebDriverException $e) {
            throw new DriverException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws DriverException
     */
    private function ensureInputType(WebDriverElement $element, string $xpath, string $type, string $action) : void
    {
        if (strtolower($element->getTagName()) !== 'input' || $type !== strtolower($element->getAttribute('type'))) {
            $message = 'Impossible to %s the element with XPath "%s" as it is not a %s input';

            throw new DriverException(sprintf($message, $action, $xpath, $type));
        }
    }

    /**
     * @throws UnsupportedDriverActionException
     */
    private function createWebDriverAction() : WebDriverActions
    {
        $webDriver = $this->client->getWebDriver();
        if (! $webDriver instanceof WebDriverHasInputDevices) {
            throw new UnsupportedDriverActionException('This action is not supported by %s.', $this);
        }

        return new WebDriverActions($webDriver);
    }

    /**
     * @param string|int $char
     *
     * @throws DriverException
     * @throws UnsupportedDriverActionException
     */
    private function dispatchKeyboardEventOn(string $xpath, string $type, $char, ?string $modifier) : void
    {
        $this->executeScriptOn(
            $this->findElementOrThrow($xpath),
            'arguments[0].dispatchEvent(new KeyboardEvent(arguments[1], arguments[2]));',
            $type,
            self::keyboardEventsOptions($char, $modifier)
        );
    }

    /**
     * @param string|int $char
     *
     * @return mixed[]
     */
    private static function keyboardEventsOptions($char, ?string $modifier) : array
    {
        return [
            'key' => is_int($char) ? chr($char) : $char,
            'keyCode' => is_string($char) ? ord($char) : $char,
            'ctrlKey' => $modifier === 'ctrl',
            'shiftKey' => $modifier === 'shift',
            'altKey' => $modifier === 'alt',
            'metaKey' => $modifier === 'meta',
        ];
    }

    /**
     * @throws UnsupportedDriverActionException
     */
    private function attachFileTo(WebDriverElement $element, string $path) : void
    {
        if (! $element instanceof RemoteWebElement) {
            throw new UnsupportedDriverActionException('Uploading a file is not supported by %s.', $this);
        }

        $element->setFileDetector(new LocalFileDetector());

        $element->sendKeys($path);
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     *
     * @throws UnsupportedDriverActionException
     */
    private function executeScriptOn(WebDriverElement $element, string $script, ...$args)
    {
        try {
            return $this->client->executeScript($script, array_merge([$element], $args));
        } catch (RuntimeException $e) {
            throw new UnsupportedDriverActionException('JavaScript is not supported by %s.', $this, $e);
        }
    }
}
