<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\ElementLocator;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Symfony\Component\Panther\Client;

/**
 * @internal
 */
final class SimpleElementLocator implements ElementLocator
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function findElement(WebDriverBy $by) : WebDriverElement
    {
        return $this->client->findElement($by);
    }

    public function findVisibleElement(WebDriverBy $by) : WebDriverElement
    {
        return $this->findElement($by);
    }

    public function findClickableElement(WebDriverBy $by): WebDriverElement
    {
        return $this->findElement($by);
    }

    /**
     * @inheritDoc
     */
    public function findElements(WebDriverBy $by) : iterable
    {
        return $this->client->findElements($by);
    }
}
