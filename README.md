# Buffered Translation

Manually pasted text on document for translation, by means of buffering is translated by one approach (helpful for DB sources).<br>
Include vendors:
 * [ali-translator/text-template](https://github.com/ali-translator/text-template)
 * [ali-translator/translator](https://github.com/ali-translator/translator) 

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
```

Use "BufferMessageFormatsEnum::MESSAGE_FORMATTER" for templates from the PECL intl packet "MessageFormatter::formatMessage()" to format a text (example `{0, plural, =0{Zero}=1{One}other{Unknown #}}`). 
```php
?>
<p>
    <?= $bufferTranslation->add(
            '{name} has {appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}', 
            [
                'appleNumbers' => 0,
                'name' => 'Тома',
            ],
            'format' => BufferMessageFormatsEnum::MESSAGE_FORMATTER,
    ) ?>
    <?= $bufferTranslation->add($stringFromDb, [], [BufferContentOptions::OPTION_WITH_HTML_ENCODING => true]) ?>

    <?= $bufferTranslation->add($stringFromDb, [], [BufferContentOptions::OPTION_WITH_HTML_ENCODING => true]) ?>   
</p>
```
Custom post-translation modification
```php
<script>
    alert('<?= $bufferTranslation->add($errorText, [], [
                  BufferContentOptions::OPTION_MODIFIER_CALLBACK => function (string $translation): string {
                       return Html::escapeJavaScriptStringValue($translation);
                  },
         ]) ?>');
</script>
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
                        BufferContentOptions::OPTION_WITH_CONTENT_TRANSLATION => true,
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
 
* <b>`BufferContentOptions::OPTION_WITH_CONTENT_TRANSLATION`</b> It's bool parameter, which indicates whether to translate included parameter.<br>
By <b>default</b>, this value is set to <b>"false"</b>.  
* <b>`BufferContentOptions::OPTION_WITH_FALLBACK`</b> Bool parameter, which determines whether the original text will be returned if no translation is found.<br> 
By <b>default</b>, this value is set to <b>"true"</b>.
* <b>`BufferContentOptions::OPTION_WITH_HTML_ENCODING`</b> - use html encode for output text 
* <b>`BufferContentOptions::OPTION_MODIFIER_CALLBACK`</b> - custom post-translation modifier 

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
