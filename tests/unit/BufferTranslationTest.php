<?php

use ALI\BufferTranslation\Buffer\BufferContent;
use ALI\BufferTranslation\Buffer\MessageFormat\MessageFormatsEnum;
use ALI\BufferTranslation\BufferTranslation;
use ALI\BufferTranslation\Tests\components\Factories\SourceFactory;
use ALI\Translator\PlainTranslator\PlainTranslatorFactory;
use ALI\Translator\Source\Exceptions\SourceException;
use PHPUnit\Framework\TestCase;

/**
 * Class
 */
class BufferTranslationTest extends TestCase
{
    const ORIGINAL_LANGUAGE = 'en';
    const CURRENT_LANGUAGE = 'ua';

    /**
     * @throws SourceException
     */
    public function test()
    {
        $sourceFactory = new SourceFactory();
        $source = $sourceFactory->generateSource(self::ORIGINAL_LANGUAGE, self::CURRENT_LANGUAGE);

        $source->saveTranslate(self::CURRENT_LANGUAGE, 'Hello', 'Привіт');
        $plainTranslator = (new PlainTranslatorFactory())->createPlainTranslator($source, self::CURRENT_LANGUAGE);

        // Empty translate
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $html = '<div class="test">' . $bufferTranslation->add('XXX') . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">XXX</div>', $translatedHtml);
        }

        // Empty translate with parameter
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $html = '<div class="test">' . $bufferTranslation->add('XXX {name}', ['name' => 'Tom']) . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">XXX Tom</div>', $translatedHtml);
        }

        // Filled translation
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $html = '<div class="test">' . $bufferTranslation->add('Hello') . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">Привіт</div>', $translatedHtml);
        }

        // Filled translation with parameter
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);

            $bufferTranslation->getPlainTranslator()->getSource()
                ->saveTranslate(self::CURRENT_LANGUAGE, 'Hello {name}', 'Привіт {name}');

            $html = '<div class="test">' . $bufferTranslation->add('Hello {name}', ['name' => 'Tom']) . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">Привіт Tom</div>', $translatedHtml);
        }

        // Filled translation, with few the same parameter name, on another BufferContent objects
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);

            $bufferTranslation->getPlainTranslator()->getSource()
                ->saveTranslate(self::CURRENT_LANGUAGE, 'Hello {name}', 'Привіт {name}');

            $html = '<div class="test">' .
                $bufferTranslation->add('Hello {name}', ['name' => 'Tom']) . '. '
                . $bufferTranslation->add('Hello {name}', ['name' => 'Kate'])
                .'</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">Привіт Tom. Привіт Kate</div>', $translatedHtml);
        }

        // Custom bufferContent, with few child
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $source = $bufferTranslation->getPlainTranslator()->getSource();
            $source
                ->saveTranslate(self::CURRENT_LANGUAGE, 'Hello {child}. Hi {object}', 'Привіт {child}. Вітаю {object}');
            // This translate will be used on for translate one of parameters
            $source
                ->saveTranslate(self::CURRENT_LANGUAGE, 'Andrea', 'Андреа');
            // This translate will be skipped
            $source
                ->saveTranslate(self::CURRENT_LANGUAGE, 'Tom', 'Том');

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

            $this->assertEquals('<div class="test">Привіт Tom and Андреа. Вітаю sun</div>', $translatedHtml);
        }

        {
            // Format message
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $originalPhrase = '{number, plural, =0{Zero}=1{One}other{Unknown #}}';

            $content = $bufferTranslation->add($originalPhrase, [
                'number' => 0,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('Zero', $translation);

            $content = '<div>' . $bufferTranslation->add($originalPhrase, [
                'number' => 0,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]) .'</div>';
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('<div>Zero</div>', $translation);

            $content = $bufferTranslation->add($originalPhrase, [
                'number' => 1,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('One', $translation);

            $content = $bufferTranslation->add($originalPhrase, [
                'number' => 50,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('Unknown 50', $translation);

            // With translation
            $plainTranslator->saveTranslate($originalPhrase,'{number, plural, =0{Нуль}=1{Один}other{Невідоме число #}}');

            $content = $bufferTranslation->add($originalPhrase, [
                'number' => 0,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('Нуль', $translation);

            $content = $bufferTranslation->add($originalPhrase, [
                'number' => 1,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('Один', $translation);

            $content = $bufferTranslation->add($originalPhrase, [
                'number' => 50,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('Невідоме число 50', $translation);
        }

        {
            // Plural with another text, without translate
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $content = $bufferTranslation->add('Tom has {appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}', [
                'appleNumbers' => 1,
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('Tom has one apple', $translation);

            // Plural with another text, on different BufferContent, without translate
            $content = $bufferTranslation->add('Tom has {appleNumbers}', [
                'appleNumbers' => [
                    'content' => '{appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}',
                    'parameters' => [
                        'appleNumbers' => 1,
                    ],
                    'options' => [
                        BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER,
                        BufferContent::OPTION_WITH_CONTENT_TRANSLATION => true,
                    ],
                ],
            ]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('Tom has one apple', $translation);
        }

        {
            // Plural with another text, WITH translate
            $originalPhrase = '{name} has {appleNumbers, plural, =0{no any apple}=1{one apple}other{many apples}}';
            $plainTranslator->saveTranslate($originalPhrase,'У {name} {appleNumbers, plural, =0{немає яблук}=1{є одне яблуко}other{є багато яблук}}');

            $bufferTranslation = new BufferTranslation($plainTranslator);

            $content = $bufferTranslation->add($originalPhrase, [
                'appleNumbers' => 0,
                'name' => 'Тома',
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('У Тома немає яблук', $translation);

            $content = $bufferTranslation->add($originalPhrase, [
                'appleNumbers' => 1,
                'name' => 'Тома',
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('У Тома є одне яблуко', $translation);

            $content = $bufferTranslation->add($originalPhrase, [
                'appleNumbers' => 4,
                'name' => 'Тома',
            ], [BufferContent::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::MESSAGE_FORMATTER]);
            $translation = $bufferTranslation->translateBuffer($content);
            $this->assertEquals('У Тома є багато яблук', $translation);
        }
    }
}
