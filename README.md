# Buffered Translation

Manually pasted text on document for translation, by means of buffering is translated by one approach (helpful for DB sources)

## Installation

```bash
$ composer require ali-translator/buffered-translation
```

### Quick start

```php
use ALI\BufferTranslation\BufferTranslation;
use ALI\Translator\PlainTranslator\PlainTranslator;

/** @var PlainTranslator $plainTranslator */

$bufferTranslation = new BufferTranslation($plainTranslator);
```

Move created `$bufferTranslation` to document creating process 

```php
/** @var \ALI\BufferTranslation\BufferTranslation $bufferTranslation */
?>
<h1>
    <?= $bufferTranslation->add('Hello World!') ?>
</h1>
<p>
    <?= $bufferTranslation->add('Hello {name}', ['name' => 'Tom']) ?>    
</p>
<p>
    <?= $bufferTranslation->add(
            '{name} has {appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}', 
            [
                'appleNumbers' => 0,
                'name' => 'Тома',
            ], 
            [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]
    ) ?>
</p>
```

And next, translate this buffered document

```php
use ALI\BufferTranslation\BufferTranslation;

/** @var BufferTranslation $bufferTranslation */
/** @var string $html */

echo $bufferTranslation->translateBuffer($html);

```

### Tips
* Every phrase for buffer translation has options parameter, with next features:
    * `BufferContent::OPTION_MESSAGE_FORMAT`
        * `MessageFormatsEnum::BUFFER_CONTENT` - allow only "plain" parameters, example "{name}", 
        but also has infinite nesting of parameters.<br> 
        It is <b>default</b> type
        * `MessageFormatsEnum::MESSAGE_FORMATTER` - use PECL intl packet [MessageFormatter::formatMessage](https://www.php.net/manual/ru/messageformatter.formatmessage.php) for text formatting.  
    * `BufferContent::OPTION_WITH_CONTENT_TRANSLATION` It's bool parameter, for translating included parameter.<br>
    In <b>default</b> this set to "false".  
    * `BufferContent::OPTION_WITH_FALLBACK` Bool parameter, which determines whether the original text will be returned if no translation is found.<br> 
    In <b>default</b> this set to "true".

### Suggest packets
* <b>[ali-translator/translator-js-integrate](https://github.com/ali-translator/translator-js-integrate)</b> - Integrate this packet to frontend js
* <b>[ali-translator/auto-html-translation](https://github.com/ali-translator/auto-html-translation)</b> - Parses html document, and translate included texts
* <b>[ali-translator/url-template](https://github.com/ali-translator/url-template)</b> - Helps on url language resolving

### Tests
In packet exist docker-compose file, with environment for testing.
```bash
docker-compose run php composer install
docker-compose run php vendor/bin/phpunit
```
