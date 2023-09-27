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

Move created `$bufferTranslation` to document creating process :

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

Use "logical variable" for plural templates: 
```php
?>
<p>
    <?= $bufferTranslation->add(
            '{name} has {|plural(appleNumbers,"=0[no one apple] =1[one apple] other[many apples]")}', 
            [
                'appleNumbers' => 0,
                'name' => 'Tom',
            ]
    ) ?>
    <?= $bufferTranslation->add($stringFromDb, [], [BufferContentOptions::OPTION_WITH_HTML_ENCODING => true]) ?>  
</p>
```
Custom post-translation modification:
```php
<script>
    alert('<?= $bufferTranslation->add($errorText, [], [
                  BufferContentOptions::MODIFIER_CALLBACK => function (string $translation): string {
                       return Html::escapeJavaScriptStringValue($translation);
                  },
         ]) ?>');
</script>
```

And then translate this buffer document:

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
                        BufferContentOptions::WITH_CONTENT_TRANSLATION => true,
                    ]
                ],
            ],
        ],
        'object' => 'sun',
    ]) . '</div>';
$translatedHtml = $bufferTranslation->translateBuffer($html);
```
Translation is recursive.


### Logical variables
Template support is implemented on the basis of the "[ali-translator/text-template](https://github.com/ali-translator/text-template)" package, which also supports "logical variables". These are variables that use "functions" to modify content. More details can be found at the link to the package.<br>
Example:<br>
```Поїздка {|uk_choosePrepositionBySonority('Поїздка', 'в/у', 'Львів')} Львів```

### Options
Every buffered phrase has translation options parameters, with next features:
 
* <b>`BufferContentOptions::WITH_CONTENT_TRANSLATION`</b> It's bool parameter, which indicates whether to translate included parameter.<br>
By <b>default</b>, this value is set to <b>"false"</b>.  
* <b>`BufferContentOptions::WITH_FALLBACK`</b> Bool parameter, which determines whether the original text will be returned if no translation is found.<br> 
By <b>default</b>, this value is set to <b>"true"</b>.
* <b>`BufferContentOptions::WITH_HTML_ENCODING`</b> - use html encode for output text 
* <b>`BufferContentOptions::MODIFIER_CALLBACK`</b> - custom post-translation modifier 


### Translation of a fragment of buffered text

If you only need to translate a single piece of buffered text, you should use the "translateBufferFragment" method:
```php
$translatedHtml = $bufferTranslation->translateBuffer($pieceOfHtml);
```
this method only translate the found keys in the given context, not all buffered text.

### Translation of buffered array

<b>! Translation of buffered arrays is less efficient than normal translation of compiled text, and should not be considered as a primary option.</b>

```php
/**
* @param array|null $columnsForTranslation - null means "all string columns"
* @param bool $isItBufferFragment - Choose whether you want to translate the entire buffer or only the existing keys in the text
 */
$translatedBufferedArray = $bufferTranslation->translateArrayWithBuffers($bufferedArray, $columnsForTrnasl, $columnsForTranslation);
```

### Hints
* If you have already a buffered key and want to use it in another template, you can use this script:
```php
$bufferTranslation->add('Some {text}',[
    'text' => $bufferTranslation->getTextTemplateItemByBufferKey($alreadyBufferedTextKey) 
]);
```

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
