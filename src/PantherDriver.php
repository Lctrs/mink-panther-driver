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
use Facebook\WebDriver\WebDriverRadios;
use Facebook\WebDriver\WebDriverSelect;
use RuntimeException;
use Symfony\Component\Panther\Client;
use Webmozart\Assert\Assert;
use function array_map;
use function array_merge;
use function chr;
use function in_array;
use function is_int;
use function is_string;
use function ord;
use function preg_match;
use function preg_replace;
use function rawurldecode;
use function rawurlencode;
use function round;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function trim;
use const PHP_EOL;

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

    public function start() : void
    {
        $this->client->start();

        $this->isStarted = true;
    }

    public function isStarted() : bool
    {
        return $this->isStarted;
    }

    public function stop() : void
    {
        $this->isStarted = false;

        $this->client->quit();
    }

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

    public function getCurrentUrl() : string
    {
        return $this->client->getCurrentURL();
    }

    public function reload() : void
    {
        $this->client->reload();
    }

    public function forward() : void
    {
        $this->client->forward();
    }

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
     * @inheritDoc
     */
    public function setCookie($name, $value = null) : void
    {
        $manager = $this->client->manage();

        if ($value === null) {
            $manager->deleteCookieNamed($name);

            return;
        }

        $this->client->manage()->addCookie(new Cookie($name, rawurlencode($value)));
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

        return rawurldecode($cookie->getValue());
    }

    public function getContent() : string
    {
        return str_replace(
            ["\r", "\r\n", "\n"],
            PHP_EOL,
            $this->client->getPageSource()
        );
    }

    public function getScreenshot() : string
    {
        return $this->client->takeScreenshot();
    }

    /**
     * @return array<int, string>
     */
    public function getWindowNames() : array
    {
        return $this->client->getWindowHandles();
    }

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
     * @inheritDoc
     */
    public function getValue($xpath)
    {
        $element = $this->findElementOrThrow($xpath);
        $tagName = $element->getTagName();

        if ($tagName === 'input') {
            $type = strtolower($element->getAttribute('type') ?? '');

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
                return $element->getAttribute('value') ?? '';
            }, $select->getAllSelectedOptions());
        }

        return $element->getAttribute('value');
    }

    /**
     * @inheritDoc
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
            $type = strtolower($element->getAttribute('type') ?? '');

            if (in_array($type, ['submit', 'image', 'button', 'reset'], true)) {
                throw new DriverException(sprintf('Impossible to set value on element with XPath "%s" as it is not a select, textarea or textbox', $xpath));
            }

            switch ($type) {
                case 'checkbox':
                    Assert::boolean($value);

                    if ($element->isSelected() xor $value) {
                        $element->click();
                    }

                    return;
                case 'radio':
                    Assert::string($value);

                    try {
                        (new WebDriverRadios($element))->selectByValue($value);
                    } catch (WebDriverException $e) {
                        throw new DriverException($e->getMessage(), 0, $e);
                    }

                    return;
                case 'file':
                    Assert::string($value);

                    $this->attachFileTo($element, $value);

                    return;
                case 'color':
                case 'date':
                case 'datetime-local':
                case 'month':
                    Assert::string($value);

                    $this->executeScriptOn(
                        $element,
                        <<<'JS'
if (arguments[0].readOnly) { return; }
if (document.activeElement !== arguments[0]){
    arguments[0].focus();
}
if (arguments[0].value !== arguments[1]) {
    arguments[0].value = arguments[1];
    arguments[0].dispatchEvent(new InputEvent('input'));
    arguments[0].dispatchEvent(new Event('change', { bubbles: true }));
}
JS
                        ,
                        $value
                    );

                    return;
            }
        }

        Assert::string($value);

        if (in_array($tagName, ['input', 'textarea'], true)) {
            $existingValueLength = strlen($element->getAttribute('value') ?? '');
            $value               = str_repeat(WebDriverKeys::BACKSPACE . WebDriverKeys::DELETE, $existingValueLength) . $value;
        }

        $this->createWebDriverAction()
            ->sendKeys(
                $element,
                $value
            )
            // Add the TAB key to ensure we unfocus the field as browsers are triggering the change event only
            // after leaving the field.
            ->sendKeys(
                $element,
                WebDriverKeys::TAB
            )
            ->perform();
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

        if ($tagName === 'input' && strtolower($element->getAttribute('type') ?? '') === 'radio') {
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
        $this->createWebDriverAction()->click(
            $this->findElementOrThrow($xpath)
        )->perform();
    }

    /**
     * @inheritdoc
     */
    public function blur($xpath) : void
    {
        $this->createWebDriverAction()->sendKeys(
            $this->findElementOrThrow($xpath),
            WebDriverKeys::TAB
        )->perform();
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
        if (preg_match('/^function[\s\(]/', $script) === 1) {
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
        $seconds = (int) round($timeout / 1000.0);
        $wait    = $this->client->wait($seconds);

        $script    = 'return ' . $condition . ';';
        $condition = static function (JavaScriptExecutor $driver) use ($script) {
            return $driver->executeScript($script);
        };

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
        if (strtolower($element->getTagName()) !== 'input'
            || $type !== strtolower($element->getAttribute('type') ?? '')
        ) {
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
            'which' => is_string($char) ? ord($char) : $char,
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
