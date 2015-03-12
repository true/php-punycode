# Punycode

[![Build Status](https://secure.travis-ci.org/true/php-punycode.png?branch=master)](http://travis-ci.org/true/php-punycode)
[![Coverage Status](https://coveralls.io/repos/true/php-punycode/badge.png?branch=master)](https://coveralls.io/r/true/php-punycode)
[![Latest Stable Version](https://poser.pugx.org/true/punycode/version.png)](https://packagist.org/packages/true/punycode)

A Bootstring encoding of Unicode for Internationalized Domain Names in Applications (IDNA).


## Install

```
composer require true/punycode:~1.0
```


## Usage

```php
<?php

// Import Punycode
use True\Punycode;

$Punycode = new Punycode();
var_dump($Punycode->encode('renangonçalves.com'));
// outputs: xn--renangonalves-pgb.com

var_dump($Punycode->decode('xn--renangonalves-pgb.com'));
// outputs: renangonçalves.com
```


## FAQ

### 1. What is this library for?

This library converts a Unicode encoded domain name to a IDNA ASCII form and vice-versa.


### 2. Why should I use this instead of [PHP's IDN Functions](http://php.net/manual/en/ref.intl.idn.php)?

If you can compile the needed dependencies (intl, libidn) there is not much difference.
But if you want to write portable code between hosts (including Windows and Mac OS), or can't install PECL extensions, this is the right library for you.
