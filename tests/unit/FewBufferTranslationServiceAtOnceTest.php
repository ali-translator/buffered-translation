<?php

namespace ALI\BufferTranslation\Tests\unit;

use ALI\BufferTranslation\BufferTranslation;
use ALI\BufferTranslation\Tests\components\Factories\SourceFactory;
use ALI\Translator\Languages\Language;
use ALI\Translator\Languages\Repositories\ArrayLanguageRepository;
use ALI\Translator\PlainTranslator\PlainTranslatorFactory;
use ALI\Translator\Source\SourceInterface;
use PHPUnit\Framework\TestCase;

class FewBufferTranslationServiceAtOnceTest extends TestCase
{
    public function test()
    {
        $languageRepository = new ArrayLanguageRepository();
        $languageRepository->save(new Language(SourceFactory::ORIGINAL_LANGUAGE_ISO, 'Language 1', SourceFactory::ORIGINAL_LANGUAGE_ALIAS), true);
        $languageRepository->save(new Language(SourceFactory::ADDITIONAL_ORIGINAL_LANGUAGE_ISO, 'Language 2', SourceFactory::ADDITIONAL_ORIGINAL_LANGUAGE_ALIAS), true);
        $languageRepository->save(new Language(SourceFactory::TRANSLATION_FOR_LANGUAGE_ISO, 'Language 3', SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS), true);

        $sourceFactory = new SourceFactory();

        // EN -> UK buffer
        {
            $firstSource = $sourceFactory->generateSource(SourceFactory::ORIGINAL_LANGUAGE_ALIAS);
            $firstSource->saveTranslate(SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Hello {animal}!', 'Привіт {animal}!');
            $firstPlainTranslator = (new PlainTranslatorFactory())->createPlainTranslator($firstSource, SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS);
            $firstBufferTranslation = new BufferTranslation($firstPlainTranslator, $languageRepository);
        }


        // RU -> UK buffer
        {
            $secondSource = $sourceFactory->generateSource(SourceFactory::ADDITIONAL_ORIGINAL_LANGUAGE_ALIAS);
            $secondSource->saveTranslate(SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS, 'Кот', 'Кіт');
            $secondPlainTranslator = (new PlainTranslatorFactory())->createPlainTranslator($secondSource, SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS);
            $secondBufferTranslation = new BufferTranslation($secondPlainTranslator, $languageRepository);
        }

        // Bae checks
        {
            // Create a "soup" of buffers
            $buffer = $firstBufferTranslation->add('Hello {animal}!', [
                'animal' => $secondBufferTranslation->createAndAddTextTemplateItem('Кот')
            ]);

            $firstBufferTranslation->preTranslateAllInsideTextTemplates();

            // Check that own "TextTemplateItem" is translated
            $ownTextTemplate = $firstBufferTranslation->getTextTemplateItemByBufferKey($buffer);
            $this->assertEquals('Привіт {animal}!', $ownTextTemplate->getContent());

            // Check that "TextTemplateItem" from the second Buffer do not translated
            $this->assertEquals(
                'Кот',
                $ownTextTemplate->getChildTextTemplatesCollection()->get('animal')->getContent()
            );

            // Now translate "$secondBufferTranslation" and again check
            $secondBufferTranslation->preTranslateAllInsideTextTemplates();
            $this->assertEquals(
                'Кіт',
                $ownTextTemplate->getChildTextTemplatesCollection()->get('animal')->getContent()
            );

            // And full translation
            $this->assertEquals('Привіт Кіт!', $firstBufferTranslation->translateBuffer($buffer));
        }

        // High investment
        {
            $buffer = $this->createHighInvestmentBuffer($firstBufferTranslation, $secondBufferTranslation, $firstSource, $secondSource);
            $this->assertEquals('Це же милий стол!', $firstBufferTranslation->translateBuffer($buffer));
            $secondBufferTranslation->preTranslateAllInsideTextTemplates();
            $this->assertEquals('Це ж милий стіл!', $firstBufferTranslation->translateBuffer($buffer));

            // Make preTranslation of all buffers
            $buffer = $this->createHighInvestmentBuffer($firstBufferTranslation, $secondBufferTranslation, $firstSource, $secondSource);
            $secondBufferTranslation->preTranslateAllInsideTextTemplates();
            $firstBufferTranslation->preTranslateAllInsideTextTemplates();
            $this->assertEquals('Це ж милий стіл!', $firstBufferTranslation->translateBuffer($buffer));
        }
    }

    protected function createHighInvestmentBuffer(
        BufferTranslation $firstBufferTranslation,
        BufferTranslation $secondBufferTranslation,
        SourceInterface $firstSource,
        SourceInterface $secondSource
    ): string
    {
        $firstBufferTranslation->flush();
        $secondBufferTranslation->flush();

        $firstSource->saveTranslate(SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS, 'It {object}!', 'Це {object}!');
        $secondSource->saveTranslate(SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS, 'же {object}', 'ж {object}');
        $firstSource->saveTranslate(SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS, 'fucking {object}', 'милий {object}');
        $secondSource->saveTranslate(SourceFactory::TRANSLATION_FOR_LANGUAGE_ALIAS, 'стол', 'стіл');

        return $firstBufferTranslation->add('It {object}!', [
            'object' => $secondBufferTranslation->createAndAddTextTemplateItem('же {object}', [
                'object' => $firstBufferTranslation->createAndAddTextTemplateItem('fucking {object}', [
                    'object' => $secondBufferTranslation->createAndAddTextTemplateItem('стол')
                ])
            ])
        ]);
    }
}
