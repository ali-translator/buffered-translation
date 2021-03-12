<?php

use ALI\BufferTranslation\Buffer\BufferContent;
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
            $html = '<div class="test">' . $bufferTranslation->addToBuffer('XXX') . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">XXX</div>', $translatedHtml);
        }

        // Empty translate with parameter
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $html = '<div class="test">' . $bufferTranslation->addToBuffer('XXX {name}', ['name' => 'Tom']) . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">XXX Tom</div>', $translatedHtml);
        }

        // Filled translation
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);
            $html = '<div class="test">' . $bufferTranslation->addToBuffer('Hello') . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">Привіт</div>', $translatedHtml);
        }

        // Filled translation with parameter
        {
            $bufferTranslation = new BufferTranslation($plainTranslator);

            $bufferTranslation->getPlainTranslator()->getSource()
                ->saveTranslate(self::CURRENT_LANGUAGE, 'Hello {name}', 'Привіт {name}');

            $html = '<div class="test">' . $bufferTranslation->addToBuffer('Hello {name}', ['name' => 'Tom']) . '</div>';
            $translatedHtml = $bufferTranslation->translateBuffer($html);

            $this->assertEquals('<div class="test">Привіт Tom</div>', $translatedHtml);
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

            $html = '<div class="test">' . $bufferTranslation->addToBuffer('Hello {child}. Hi {object}', [
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
    }
}
