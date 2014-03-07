# Punycode

A Bootstring encoding of Unicode for Internationalized Domain Names in Applications (IDNA).


## Install

```
composer require true/punycode:*
```


## Usage

```php
<?php

// Import Punycode
use True\Punycode;

// Use UTF-8 as the encoding
mb_internal_encoding('utf-8');

$Punycode = new Punycode();
var_dump($Punycode->encode('renangonçalves.com'));
// outputs: xn--renangonalves-pgb.com

var_dump($Punycode->decode('xn--renangonalves-pgb.com'));
// outputs: renangonçalves.com
```


## FAQ

### 1. What is this library for?

This library converts a UTF-8 encoded domain name to a IDNA ASCII form and vice-versa.


### 2. Do I need to use UTF-8?

Yes, domain names should be UTF-8 encoded.

Unless your application is not focused on international users, you should have been using a Unicode charset already.
Take your time to read [The Absolute Minimum Every Software Developer Must Know About Unicode](http://www.joelonsoftware.com/articles/Unicode.html).


### 3. Why should I use this instead of [PHP's IDN Functions](http://php.net/manual/en/ref.intl.idn.php)?

If you can compile the needed dependencies (intl, libidn) there is not much difference.
But if you want to write portable code between hosts (including Windows and Mac OS), or can't install PECL extensions, this is the right library for you.
