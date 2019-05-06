# mink-panther-driver

A [Symfony Panther](https://github.com/symfony/panther) driver for [Mink framework](http://mink.behat.org/en/latest/) .

[![License](https://img.shields.io/github/license/Lctrs/mink-panther-driver.svg?style=flat-square)](https://github.com/Lctrs/mink-panther-driver/blob/master/LICENSE)
[![Current release](https://img.shields.io/packagist/v/lctrs/mink-panther-driver.svg?style=flat-square)](https://packagist.org/packages/lctrs/mink-panther-driver)
[![Travis-CI build status](https://img.shields.io/travis/Lctrs/mink-panther-driver.svg?style=flat-square)](https://travis-ci.org/Lctrs/mink-panther-driver)

## Installation

```sh
composer require lctrs/mink-panther-driver
```

## Usage

### With chromedriver

```php
<?php

use Behat\Mink\Mink;
use Behat\Mink\Session;
use Lctrs\MinkPantherDriver\PantherDriver;

$mink = new Mink([
    'panther' => new Session(
        PantherDriver::createChromeDriver('/path/to/executable', ['some', 'arguments'], ['scheme' => 'https'])
    ),
]);
```

### With Selenium

```php
<?php

use Behat\Mink\Mink;
use Behat\Mink\Session;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Lctrs\MinkPantherDriver\PantherDriver;

$mink = new Mink([
    'panther' => new Session(
        PantherDriver::createSeleniumDriver('http://localhost:4444/wd/hub', DesiredCapabilities::firefox())
    ),
]);
```

### With a custom Panther client

```php
<?php

use Behat\Mink\Mink;
use Behat\Mink\Session;
use Lctrs\MinkPantherDriver\PantherDriver;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ProcessManager\SeleniumManager;

$client = new Client(new SeleniumManager());

$mink = new Mink([
    'panther' => new Session(
        new PantherDriver($client)
    ),
]);
```

## Documentation

mink-panther-driver is juste a glue betweek Mink and Symfony Panther, see their respective documentations :

* For `Mink`, read [Mink's documentation](http://mink.behat.org/en/latest/)
* For `Panther`, read [Symfony Panther's documentation](https://github.com/symfony/panther)
* For usage with `Behat`, read [Behat's documentation](http://behat.org/en/latest/guides.html)
