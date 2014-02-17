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

var_dump(Punycode::encode('renangonçalves.com'));
// outputs: xn--renangonalves-pgb.com

var_dump(Punycode::decode('xn--renangonalves-pgb.com'));
// outputs: renangonçalves.com
```
