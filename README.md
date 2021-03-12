# Buffered Translation

Manually pasted text on document for translation, by means of buffering is translated by one approach (helpful for DB sources)

## Installation

```bash
$ composer require ali-translator/buffered-translation
```
### Suggest packets
* <b>ali-translator/translator-js-integrate</b> - Integrate this packet to frontend js
* <b>ali-translator/auto-html-translation</b> - Parses html document, and translate included texts
* <b>ali-translator/url-template</b> - Helps on url language resolving

### Tests
In packet exist docker-compose file, with environment for testing.
```bash
docker-compose run php composer install
docker-compose run php vendor/bin/phpunit
```
