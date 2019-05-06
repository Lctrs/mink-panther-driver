<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\Bridge\Facebook\WebDriver;

use Facebook\WebDriver\Cookie as FacebookCookie;
use InvalidArgumentException;
use function sprintf;
use function strpos;

final class Cookie extends FacebookCookie
{
    /** @var mixed[] */
    protected $cookie = [];

    /**
     * @inheritDoc
     */
    public static function createFromArray(array $cookieArray)
    {
        if (! isset($cookieArray['name'])) {
            throw new InvalidArgumentException('Cookie name should be set');
        }

        if (! isset($cookieArray['value'])) {
            throw new InvalidArgumentException('Cookie value should be set');
        }

        $cookie = new self($cookieArray['name'], $cookieArray['value']);

        if (isset($cookieArray['path'])) {
            $cookie->setPath($cookieArray['path']);
        }
        if (isset($cookieArray['domain'])) {
            $cookie->setDomain($cookieArray['domain']);
        }
        if (isset($cookieArray['expiry'])) {
            $cookie->setExpiry($cookieArray['expiry']);
        }
        if (isset($cookieArray['secure'])) {
            $cookie->setSecure($cookieArray['secure']);
        }
        if (isset($cookieArray['httpOnly'])) {
            $cookie->setHttpOnly($cookieArray['httpOnly']);
        }

        return $cookie;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->offsetGet('name');
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->offsetGet('value');
    }

    /**
     * @inheritDoc
     */
    public function setPath($path)
    {
        $this->offsetSet('path', $path);
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->offsetGet('path');
    }

    /**
     * @inheritDoc
     */
    public function setDomain($domain)
    {
        if (strpos($domain, ':') !== false) {
            throw new InvalidArgumentException(sprintf('Cookie domain "%s" should not contain a port', $domain));
        }

        $this->offsetSet('domain', $domain);
    }

    /**
     * @inheritDoc
     */
    public function getDomain()
    {
        return $this->offsetGet('domain');
    }

    /**
     * @inheritDoc
     */
    public function setExpiry($expiry)
    {
        $this->offsetSet('expiry', (int) $expiry);
    }

    /**
     * @inheritDoc
     */
    public function getExpiry()
    {
        return $this->offsetGet('expiry');
    }

    /**
     * @inheritDoc
     */
    public function setSecure($secure)
    {
        $this->offsetSet('secure', $secure);
    }

    /**
     * @inheritDoc
     */
    public function isSecure()
    {
        return $this->offsetGet('secure');
    }

    /**
     * @inheritDoc
     */
    public function setHttpOnly($httpOnly)
    {
        $this->offsetSet('httpOnly', $httpOnly);
    }

    /**
     * @inheritDoc
     */
    public function isHttpOnly()
    {
        return $this->offsetGet('httpOnly');
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->cookie[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->cookie[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value) : void
    {
        if ($value === null) {
            unset($this->cookie[$offset]);
        } else {
            $this->cookie[$offset] = $value;
        }
    }
}
