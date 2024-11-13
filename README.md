# Buffered Translation

Manually pasted text in a document for translation, using buffering, is translated by one approach (helpful for DB sources). This package includes the following vendors:
 * [ali-translator/text-template](https://github.com/ali-translator/text-template)
 * [ali-translator/translator](https://github.com/ali-translator/translator) 

## Installation

```bash
$ composer require ali-translator/buffered-translation
```

### Quick start
Since this package extends from <b>[ali-translator/translator](https://github.com/ali-translator/translator)</b>,
you need to create `$translator` and a wrapper with the vector of its translation - `$plainTranslator`.

```php
use ALI\BufferTranslation\BufferTranslation;
use ALI\Translator\PlainTranslator\PlainTranslator;
use ALI\Translator\Languages\LanguageRepositoryInterface;

/** @var PlainTranslator $plainTranslator */
/** @var LanguageRepositoryInterface $languageRepository */

$bufferTranslation = new BufferTranslation($plainTranslator, $languageRepository);
```

Move the created `$bufferTranslation` to the document creation process:

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

Use "logical variables" for plural templates:
```php
?>
<p>
    <?= $bufferTranslation->add(
            '{name} has {plural(appleNumbers,"=0[no one apple] =1[one apple] other[many apples]")}', 
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

And then translate this buffered document:

```php
use ALI\BufferTranslation\BufferTranslation;

/** @var BufferTranslation $bufferTranslation */
/** @var string $html */

echo $bufferTranslation->translateBuffer($html);
```

### Multiple Level Parameters and Their Options

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
```Поїздка {uk_choosePrepositionBySonority('Поїздка', 'в/у', 'Львів')} Львів```

### Options
Every buffered phrase has translation options parameters, with the following features:
 
* <b>`BufferContentOptions::WITH_CONTENT_TRANSLATION`</b> - This boolean parameter indicates whether to translate included parameters. By default, this value is set to "false".  
* <b>`BufferContentOptions::WITH_FALLBACK`</b> This boolean parameter determines whether the original text will be returned if no translation is found. By default, this value is set to "true".
* <b>`BufferContentOptions::WITH_HTML_ENCODING`</b> - Use HTML encoding for output text.
* <b>`BufferContentOptions::MODIFIER_CALLBACK`</b> - Custom post-translation modifier. 
* <b>`BufferContentOptions::FINALLY_MODIFIER_CALLBACK`</b> - A custom modifier that is called after translation and after resolving all children. 


### Translation of a Fragment of Buffered Text

If you only need to translate a single piece of buffered text, use the translateBufferFragment method:
```php
$translatedHtml = $bufferTranslation->translateBuffer($pieceOfHtml);
```
This method only translates the found keys in the given context, not all buffered text.

### Translation of Buffered Array

Note: Translation of buffered arrays is less efficient than normal translation of compiled text and should not be considered as a primary option.

```php
/**
* @param array|null $columnsForTranslation - null means "all string columns"
* @param bool $isItBufferFragment - Choose whether you want to translate the entire buffer or only the existing keys in the text
 */
$translatedBufferedArray = $bufferTranslation->translateArrayWithBuffers($bufferedArray, $columnsForTrnasl, $columnsForTranslation);
```

### Hints
* If you already have a buffered key and want to use it in another template, you can use this script:
```php
$bufferTranslation->add('Some {text}',[
    'text' => $bufferTranslation->getTextTemplateItemByBufferKey($alreadyBufferedTextKey) 
]);
```
* If you use several BufferedTranslation services at once (for example, if the language of texts in the code and dynamic texts from the database is different) and you need to use the translation of one of the texts in the second template, it is recommended to do so:
```php
$buffer = $firstBufferTranslation->add('Some {text}',[
    'text' => $secondBufferTranslation->createAndAddTextTemplateItem('текст') 
]);
```
Later, before "resolve", you need to call the preTranslateAllInsideTextTemplates method, which will translate all registered templates:
```php
$firstBufferTranslation->preTranslateAllInsideTextTemplates();
$secondBufferTranslation->preTranslateAllInsideTextTemplates();

$buffer = $firstBufferTranslation->translateBuffer($buffer);
$result = $secondBufferTranslation->translateBuffer($buffer);
```
More details can be found in the test code: [./tests/unit/FewBufferTranslationServiceAtOnceTest.php](./tests/unit/FewBufferTranslationServiceAtOnceTest.php)

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
