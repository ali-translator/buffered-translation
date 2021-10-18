# Buffered Translation

Manually pasted text on document for translation, by means of buffering is translated by one approach (helpful for DB sources)

## Installation

```bash
$ composer require ali-translator/buffered-translation
```

### Quick start

Since this package extended from <b>[ali-translator/translator](https://github.com/ali-translator/translator)</b>,
at first you need create `$translator` and wrapper, with vector of his translation - `$plaiTranslator`

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

### Multiple level parameters, and they options

```php
$html = '<div class="test">' . $bufferTranslation->add('Hello {child}. Hi {object}', [
        'child' => [
            'content' => 'Tom and {secondName}',
            'parameters' => [
                'secondName' => [
                    'content' => 'Andrea',
                    'options' => [
                        BufferContent::OPTION_WITH_CONTENT_TRANSLATION => true,
                    ]
                ],
            ],
        ],
        'object' => 'sun',
    ]) . '</div>';
$translatedHtml = $bufferTranslation->translateBuffer($html);
```

### Options
Every buffered phrase has translation options parameters, with next features:
* <b>`BufferContent::OPTION_MESSAGE_FORMAT`</b>
    * <b>`MessageFormatsEnum::BUFFER_CONTENT`</b> - allow only "plain" parameters, example "{name}",  
    but also has infinite nesting of parameters.<br> 
    It is <b>default</b> type
    * <b>`MessageFormatsEnum::MESSAGE_FORMATTER`</b> - uses PECL intl packet [MessageFormatter::formatMessage](https://www.php.net/manual/ru/messageformatter.formatmessage.php) for text formatting.  
* <b>`BufferContent::OPTION_WITH_CONTENT_TRANSLATION`</b> It's bool parameter, which indicates whether to translate included parameter.<br>
By <b>default</b>, this value is set to <b>"false"</b>.  
* <b>`BufferContent::OPTION_WITH_FALLBACK`</b> Bool parameter, which determines whether the original text will be returned if no translation is found.<br> 
By <b>default</b>, this value is set to <b>"true"</b>.
* <b>`BufferContent::OPTION_WITH_HTML_ENCODING`</b> - use html encode for output text 

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
