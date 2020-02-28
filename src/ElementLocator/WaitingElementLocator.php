<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\ElementLocator;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Panther\Client;

/**
 * @internal
 */
final class WaitingElementLocator implements ElementLocator
{
    private const EXPLICIT_WAIT = 5;

    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function findElement(WebDriverBy $by) : WebDriverElement
    {
        return $this->client->getWebDriver()->wait(self::EXPLICIT_WAIT)->until(
            WebDriverExpectedCondition::presenceOfElementLocated($by),
            'no such element'
        );
    }

    public function findVisibleElement(WebDriverBy $by) : WebDriverElement
    {
        return $this->client->getWebDriver()->wait(self::EXPLICIT_WAIT)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated($by),
            'no such element'
        );
    }

    public function findClickableElement(WebDriverBy $by): WebDriverElement
    {
        return $this->client->getWebDriver()->wait(self::EXPLICIT_WAIT)->until(
            WebDriverExpectedCondition::elementToBeClickable($by),
            'no such element'
        );
    }

    /**
     * @inheritDoc
     */
    public function findElements(WebDriverBy $by) : iterable
    {
        return $this->client->getWebDriver()->wait(self::EXPLICIT_WAIT)->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy($by),
            'no such elements'
        );
    }
}
