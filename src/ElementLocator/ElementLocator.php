<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\ElementLocator;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

/**
 * @internal
 */
interface ElementLocator
{
    /**
     * @throws WebDriverException
     */
    public function findElement(WebDriverBy $by) : WebDriverElement;

    /**
     * @throws WebDriverException
     */
    public function findVisibleElement(WebDriverBy $by) : WebDriverElement;

    /**
     * @throws WebDriverException
     */
    public function findClickableElement(WebDriverBy $by) : WebDriverElement;

    /**
     * @return iterable<WebDriverElement>
     *
     * @throws WebDriverException
     */
    public function findElements(WebDriverBy $by) : iterable;
}
