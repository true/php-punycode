# Changelog

## 1.1.0 - 2015-03-12

- [Enhancement] Character encoding is now passed to the constructor, defaulting to UTF-8, as opposite to relying on `mb_internal_encoding` function call (#9).

## 1.0.1 - 2014-08-26

- [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) compliant and automation on Travis-CI
  - Thanks to [@nyamsprod](https://github.com/nyamsprod) for initial patch.
- [Fix] Domain containing `x`, `n` or `-` would result in failures while decoding (#6).


## 1.0.0 - 2014-03-10

- Initial release
