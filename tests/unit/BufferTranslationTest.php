<?php

namespace ALI\BufferTranslation\Tests\unit;

use ALI\BufferTranslation\Buffer\BufferContentOptions;
use ALI\BufferTranslation\BufferTranslation;
use ALI\BufferTranslation\Tests\components\Factories\SourceFactory;
use ALI\BufferTranslation\Buffer\BufferMessageFormatsEnum;
use ALI\TextTemplate\TemplateResolver\Template\KeyGenerators\StaticKeyGenerator;
use ALI\Translator\Languages\Language;
use ALI\Translator\Languages\LanguageRepositoryInterface;
use ALI\Translator\Languages\Repositories\ArrayLanguageRepository;
use ALI\Translator\PlainTranslator\PlainTranslator;
use ALI\Translator\PlainTranslator\PlainTranslatorFactory;
use PHPUnit\Framework\TestCase;

class BufferTranslationTest extends TestCase
{
    const ORIGINAL_LANGUAGE_ALIAS = 'en';
    const ORIGINAL_LANGUAGE_ISO = 'en';
    const TRANSLATION_FOR_LANGUAGE_ALIAS = 'ua';
    const TRANSLATION_FOR_LANGUAGE_ISO = 'ua';

    public function test()
    {
        $languageRepository = new ArrayLanguageRepository();
        $languageRepository->save(new Language(self::ORIGINAL_LANGUAGE_ISO, 'English', self::ORIGINAL_LANGUAGE_ALIAS), true);
        $languageRepository->save(new Language(self::TRANSLATION_FOR_LANGUAGE_ISO, 'English', self::TRANSLATION_FOR_LANGUAGE_ALIAS), true);

        $sourceFactory = new SourceFactory();
        $source = $sourceFactory->generateSource(self::ORIGINAL_LANGUAGE_ALIAS, self::TRANSLATION_FOR_LANGUAGE_ALIAS);

        $source->saveTranslate(self::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Hello', 'Привіт');
        $plainTranslator = (new PlainTranslatorFactory())->createPlainTranslator($source, self::TRANSLATION_FOR_LANGUAGE_ALIAS);
        $bufferTranslation = new BufferTranslation($plainTranslator, $languageRepository);

        $this->emptyTranslate($bufferTranslation);

        $this->emptyTranslateWithParameter($bufferTranslation);

        $this->filledTranslation($bufferTranslation);

        $this->filledPartTranslation($bufferTranslation);

        $this->filledTranslationWithParameter($bufferTranslation);

        $this->filledTranslationWithFewTheSameParameterName($bufferTranslation);

        $this->customBufferContentWithFewChild($bufferTranslation);

        $originalPhrase = '{number, plural, =0{Zero}=1{One}other{Unknown #}}';
        $this->formatMessage($bufferTranslation, $originalPhrase);
        $this->withTranslation($bufferTranslation, $plainTranslator, $originalPhrase);

        $this->pluralWithAnotherTextWithoutTranslate($bufferTranslation);
        $this->pluralWithAnotherTextWithoutTranslateOnDifferentBufferOptions($bufferTranslation);

        $this->pluralWithAnotherTextWITHTranslate($bufferTranslation, $plainTranslator);

        $this->translateWithEncoding($bufferTranslation);
        $this->translateWithEncodingAndIncludeParameters($bufferTranslation);
        $this->translateWithEncodingAndIncludeParametersWithEncoding($bufferTranslation);

        $this->checkPreventingBufferingExistBufferedKey($bufferTranslation);
        $this->resolveTwoParametersWithTheSameValue($bufferTranslation);
        $this->checkBufferTranslationWithCallbackModifier($bufferTranslation);

        $this->checkAdditionalPublicMethods($bufferTranslation);

        $this->checkDefaultChildBuffersTranslation($plainTranslator, $languageRepository);

        $this->testTranslateBufferArray($plainTranslator, $languageRepository);

        $this->testExtractingTextTemplateByBufferKey($bufferTranslation);

        $this->testTemplatesWithLogicVariables($bufferTranslation);
    }

    protected function testTemplatesWithLogicVariables(BufferTranslation $bufferTranslation)
    {
        $template = "Tom {|print('has')} {|plural(appleNumbers,'=0[no one apple] =1[one apple] other[many apples]')}";
        $bufferKey = $bufferTranslation->add($template, [
            'appleNumbers' => 1,
        ]);

        $this->assertEquals('Tom has one apple', $bufferTranslation->translateBuffer($bufferKey));
    }

    protected function testExtractingTextTemplateByBufferKey(BufferTranslation $bufferTranslation)
    {
        $bufferTranslation->flush();

        $text = 'TEXT';

        $bufferKey = $bufferTranslation->add($text);
        $templateText = $bufferTranslation->getTextTemplateItemByBufferKey($bufferKey);

        $this->assertEquals($text, $templateText->resolve());

        $bufferKey = $bufferTranslation->add('Some {text}',[
            'text' =>  $templateText
        ]);
        $this->assertEquals('Some '. $text, $bufferTranslation->translateBuffer($bufferKey));
    }

    protected function testTranslateBufferArray(PlainTranslator $plainTranslator, LanguageRepositoryInterface $languageRepository)
    {
        $plainTranslator->getSource()->saveTranslate($plainTranslator->getTranslationLanguageAlias(), 'apples', 'яблока');
        $plainTranslator->getSource()->saveTranslate($plainTranslator->getTranslationLanguageAlias(), 'chair', 'стілець');

        $parentsTemplatesKeyGenerator = new StaticKeyGenerator('{s','}');

        $bufferTranslation = new BufferTranslation($plainTranslator, $languageRepository, $parentsTemplatesKeyGenerator);
        $bufferTranslation->add('text not included on array');
        $array = [
            [
                'title' => 'some '.$bufferTranslation->add('apples'),
                'array' => [],
                'name' => $bufferTranslation->add('chair'),
            ]
        ];

        $translatedArray = $bufferTranslation->translateArrayWithBuffers($array, null, true);
        $this->assertEquals([[
            "title" => "some яблока",
            "array" => [],
            "name" => "стілець",
        ]],$translatedArray);
    }

    protected function checkBufferTranslationWithCallbackModifier(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $text = 'TEXT';
        $content = $bufferTranslation->add($text, [], [
            BufferContentOptions::MODIFIER_CALLBACK => function (string $translation): string {
                return '+' . $translation . '-';
            },
        ]);
        $content .= $bufferTranslation->add($text);
        $translation = $bufferTranslation->translateBuffer($content);
        self::assertEquals('+' . $text . '-' . $text, $translation);
    }

    protected function resolveTwoParametersWithTheSameValue(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $bufferedContent = $bufferTranslation->add('{a}{b}', [
            'a' => 1,
            'b' => 1,
        ]);
        $translated = $bufferTranslation->translateBuffer($bufferedContent);
        self::assertEquals('11', $translated);
    }

    protected function checkPreventingBufferingExistBufferedKey(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $text = 'TEXT';
        $content1 = $bufferTranslation->add($text);
        $content2 = $bufferTranslation->add($content1);
        self::assertEquals($content1, $content2);
        $content3 = $bufferTranslation->add($content2);
        $translation = $bufferTranslation->translateBuffer($content3);
        self::assertEquals($text, $translation);
    }

    protected function translateWithEncodingAndIncludeParametersWithEncoding(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $content = $bufferTranslation->add('<div>This is {h1}</div>', [
            'h1' => [
                'content' => '<h1>H1</h1>',
                'options' => [
                    BufferContentOptions::WITH_HTML_ENCODING => true,
                ],
            ],
        ], [
            BufferContentOptions::WITH_HTML_ENCODING => true,
        ]);
        $translation = $bufferTranslation->translateBuffer($content);

        self::assertEquals('&lt;div&gt;This is &lt;h1&gt;H1&lt;/h1&gt;&lt;/div&gt;', $translation);
        self::assertEquals('<div>This is <h1>H1</h1></div>', html_entity_decode($translation));
    }

    protected function translateWithEncodingAndIncludeParameters(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $content = $bufferTranslation->add('<div>This is {h1}</div>', [
            'h1' => [
                'content' => '<h1>H1</h1>',
            ],
        ], [
            BufferContentOptions::WITH_HTML_ENCODING => true,
        ]);
        $translation = $bufferTranslation->translateBuffer($content);

        self::assertEquals('&lt;div&gt;This is <h1>H1</h1>&lt;/div&gt;', $translation);
        self::assertEquals('<div>This is <h1>H1</h1></div>', html_entity_decode($translation));
    }

    protected function translateWithEncoding(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $text = '<div class="test">Tom</div>';
        $content = $bufferTranslation->add($text, [], [
            BufferContentOptions::WITH_HTML_ENCODING => true,
        ]);
        $translation = $bufferTranslation->translateBuffer($content);
        self::assertEquals('&lt;div class="test"&gt;Tom&lt;/div&gt;', $translation);
        self::assertEquals(html_entity_decode($translation), $text);
    }

    protected function pluralWithAnotherTextWITHTranslate(BufferTranslation $bufferTranslation, PlainTranslator $plainTranslator): void
    {
        $bufferTranslation->flush();
        $originalPhrase = '{name} has {appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}';
        $originalName = 'Tom';

        $plainTranslator->saveTranslate($originalName, 'Тома');
        $plainTranslator->saveTranslate($originalPhrase, 'У {name} {appleNumbers, plural, =0{немає яблук}=1{є одне яблуко}other{є багато яблук}}');

        $content = $bufferTranslation->add($originalPhrase, [
            'appleNumbers' => 0,
            'name' => [
                'content' => $originalName,
                'options' => [
                    BufferContentOptions::WITH_CONTENT_TRANSLATION => true,
                ],
            ],
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('У Тома немає яблук', $translation);

        $content = $bufferTranslation->add($originalPhrase, [
            'appleNumbers' => 1,
            'name' => 'Тома',
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('У Тома є одне яблуко', $translation);

        $content = $bufferTranslation->add($originalPhrase, [
            'appleNumbers' => 4,
            'name' => 'Тома',
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('У Тома є багато яблук', $translation);
    }

    protected function pluralWithAnotherTextWithoutTranslate(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $content = $bufferTranslation->add('Tom has {appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}', [
            'appleNumbers' => 1,
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Tom has one apple', $translation);
    }

    protected function pluralWithAnotherTextWithoutTranslateOnDifferentBufferOptions(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $content = $bufferTranslation->add('Tom has {appleNumbers}', [
            'appleNumbers' => [
                'content' => '{appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}',
                'parameters' => [
                    'appleNumbers' => 1,
                ],
                'options' => [
                    BufferContentOptions::WITH_CONTENT_TRANSLATION => true,
                ],
                'format' => BufferMessageFormatsEnum::MESSAGE_FORMATTER,
            ],
        ]);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Tom has one apple', $translation);
    }

    protected function withTranslation(BufferTranslation $bufferTranslation, PlainTranslator $plainTranslator, string $originalPhrase): void
    {
        $bufferTranslation->flush();
        $plainTranslator->saveTranslate($originalPhrase, '{number, plural, =0{Нуль}=1{Один}other{Невідоме число #}}');

        $content = $bufferTranslation->add($originalPhrase, [
            'number' => 0,
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Нуль', $translation);

        $content = $bufferTranslation->add($originalPhrase, [
            'number' => 1,
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Один', $translation);

        $content = $bufferTranslation->add($originalPhrase, [
            'number' => 50,
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Невідоме число 50', $translation);
    }

    protected function formatMessage(BufferTranslation $bufferTranslation, string $originalPhrase): void
    {
        $bufferTranslation->flush();

        $content = $bufferTranslation->add($originalPhrase, [
            'number' => 0,
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Zero', $translation);

        $content = '<div>' . $bufferTranslation->add($originalPhrase, [
                'number' => 0,
            ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER) . '</div>';
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('<div>Zero</div>', $translation);

        $content = $bufferTranslation->add($originalPhrase, [
            'number' => 1,
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('One', $translation);

        $content = $bufferTranslation->add($originalPhrase, [
            'number' => 50,
        ], [], BufferMessageFormatsEnum::MESSAGE_FORMATTER);
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Unknown 50', $translation);
    }

    protected function customBufferContentWithFewChild(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $source = $bufferTranslation->getPlainTranslator()->getSource();
        $source
            ->saveTranslate(self::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Hello {child}. Hi {object}', 'Привіт {child}. Вітаю {object}');
        // This translation will be used on for translate one of parameters
        $source
            ->saveTranslate(self::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Andrea', 'Андреа');
        // This translation will be skipped
        $source
            ->saveTranslate(self::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Tom', 'Том');

        $html = '<div class="test">' . $bufferTranslation->add('Hello {child}. Hi {object}', [
                'child' => [
                    'content' => 'Tom and {secondName}',
                    'parameters' => [
                        'secondName' => [
                            'content' => 'Andrea',
                            'options' => [
                                BufferContentOptions::WITH_CONTENT_TRANSLATION => true,
                            ],
                        ],
                    ],
                ],
                'object' => 'sun',
            ]) . '</div>';
        $translatedHtml = $bufferTranslation->translateBuffer($html);

        $this->assertEquals('<div class="test">Привіт Tom and Андреа. Вітаю sun</div>', $translatedHtml);
    }

    protected function filledTranslationWithFewTheSameParameterName(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $bufferTranslation->getPlainTranslator()->getSource()
            ->saveTranslate(self::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Hello {name}', 'Привіт {name}');

        $html = '<div class="test">' .
            $bufferTranslation->add('Hello {name}', ['name' => 'Tom']) . '. '
            . $bufferTranslation->add('Hello {name}', ['name' => 'Kate'])
            . '</div>';
        $translatedHtml = $bufferTranslation->translateBuffer($html);

        $this->assertEquals('<div class="test">Привіт Tom. Привіт Kate</div>', $translatedHtml);
        $bufferTranslation->flush();
    }

    protected function filledTranslationWithParameter(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $bufferTranslation->getPlainTranslator()->getSource()
            ->saveTranslate(self::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Hello {name}', 'Привіт {name}');

        $html = '<div class="test">' . $bufferTranslation->add('Hello {name}', ['name' => 'Tom']) . '</div>';
        $translatedHtml = $bufferTranslation->translateBuffer($html);

        $this->assertEquals('<div class="test">Привіт Tom</div>', $translatedHtml);
    }

    protected function filledTranslation(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $html = '<div class="test">' . $bufferTranslation->add('Hello') . '</div>';
        $translatedHtml = $bufferTranslation->translateBuffer($html);

        $this->assertEquals('<div class="test">Привіт</div>', $translatedHtml);
    }

    protected function filledPartTranslation(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $html = '<div class="test">' . $bufferTranslation->add('1') . '</div>';
        $bufferTranslation->add('2');

        $translatedHtml = $bufferTranslation->translateBufferFragment($html);

        $this->assertEquals('<div class="test">1</div>', $translatedHtml);
    }

    protected function emptyTranslateWithParameter(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $html = '<div class="test">' . $bufferTranslation->add('XXX {name}', ['name' => 'Tom']) . '</div>';
        $translatedHtml = $bufferTranslation->translateBuffer($html);

        $this->assertEquals('<div class="test">XXX Tom</div>', $translatedHtml);
    }

    protected function emptyTranslate(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();

        $html = '<div class="test">' . $bufferTranslation->add('XXX') . '</div>';
        $translatedHtml = $bufferTranslation->translateBuffer($html);

        $this->assertEquals('<div class="test">XXX</div>', $translatedHtml);
    }

    protected function checkAdditionalPublicMethods(BufferTranslation $bufferTranslation): void
    {
        $bufferTranslation->flush();
        $textTemplate = $bufferTranslation->createTextTemplateItem('Hello {user_name}', [
            'user_name' => 'Tom',
        ]);
        $content = $bufferTranslation->addTextTemplateItem($textTemplate);
        $translated = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('Hello Tom', $translated);
    }

    protected function checkDefaultChildBuffersTranslation(PlainTranslator $plainTranslator, LanguageRepositoryInterface $languageRepository): void
    {
        $bufferTranslation = new BufferTranslation($plainTranslator, $languageRepository, null, null, null, [
            BufferContentOptions::WITH_HTML_ENCODING => true,
            BufferContentOptions::MODIFIER_CALLBACK => function (string $translation) {
                return '@' . $translation . '@';
            },
        ]);
        $content = '<p>' . $bufferTranslation->add('<br>') . '</p>';
        $translation = $bufferTranslation->translateBuffer($content);
        $this->assertEquals('<p>@&lt;br&gt;@</p>', $translation);
    }
}
