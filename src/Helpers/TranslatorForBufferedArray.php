<?php

namespace ALI\BufferTranslation\Helpers;

use ALI\BufferTranslation\Buffer\BufferTranslator;
use ALI\TextTemplate\KeyGenerators\KeyGenerator;
use ALI\TextTemplate\KeyGenerators\TextKeysHandler;
use ALI\TextTemplate\TextTemplateItem;
use ALI\TextTemplate\TextTemplatesCollection;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;

class TranslatorForBufferedArray
{
    protected TextKeysHandler $textKeysHandler;
    private BufferTranslator $bufferTranslator;


    public function __construct()
    {
        $this->textKeysHandler = new TextKeysHandler();
        $this->bufferTranslator = new BufferTranslator();
    }

    public function translate(
        array                    $arraysWithContents,
        TextTemplatesCollection  $textTemplatesCollection,
        PlainTranslatorInterface $plainTranslator,
        KeyGenerator             $keyGenerator,
        ?array                   $columns,
        bool                     $isItBufferFragment,
        array $defaultChildBufferContentOptions
    ): array
    {
        if ($isItBufferFragment) {
            $existTextsIs = $this->getUserTextItemIds($arraysWithContents, $columns, $keyGenerator);
            if (!$existTextsIs) {
                return $arraysWithContents;
            }
            $textTemplatesCollection = $textTemplatesCollection->sliceByKeys($existTextsIs);
            if (!$textTemplatesCollection->getArray()) {
                return $arraysWithContents;
            }
        }

        $combinedTextItemText = new TextTemplateItem('', $textTemplatesCollection);
        $translatedCombinedTextItemText = $this->bufferTranslator->translateTextTemplate($combinedTextItemText, $plainTranslator, $defaultChildBufferContentOptions);

        return $this->replaceArrayBufferedValues($translatedCombinedTextItemText, $arraysWithContents, $columns, $keyGenerator);
    }

    protected function mapColumns(
        array    &$arraysWithContents,
        ?array   $columns,
        callable $callback
    ): void
    {
        foreach ($arraysWithContents as $columnName => &$value) {
            if (is_array($value)) {
                $this->mapColumns($value, $columns, $callback);
            }
            if ($columns && !in_array($columnName, $columns)) {
                continue;
            }
            if (!is_string($value)) {
                continue;
            }

            $value = $callback($columnName, $value) ?? $value;
        }
    }

    protected function getUserTextItemIds(
        array $arraysWithContents,
        ?array $columns,
        KeyGenerator $keyGenerator
    ): array
    {
        $existTextKeys = [];
        $this->mapColumns($arraysWithContents, $columns, function ($columnName, $value) use ($keyGenerator, &$existTextKeys) {
            $existKeysItem = $this->textKeysHandler->getAllKeys($keyGenerator, $value);
            if ($existKeysItem) {
                $existTextKeys[] = $existKeysItem;
            }
        });
        if ($existTextKeys) {
            $existTextKeys = array_merge(...$existTextKeys);
        }

        return $existTextKeys;
    }

    protected function replaceArrayBufferedValues(
        TextTemplateItem $translatedCombinedTextItemText,
        array $arraysWithContents,
        ?array $columns,
        KeyGenerator $keyGenerator
    ): array
    {
        $indexedTranslatedTextItems = $translatedCombinedTextItemText->getChildTextTemplatesCollection()->getArray();

        // Translate array by link
        $this->mapColumns($arraysWithContents, $columns, function ($columnName, &$value) use ($keyGenerator, $indexedTranslatedTextItems) {
            $value = $this->textKeysHandler->replaceKeys($keyGenerator, $value, function (string $textItemId) use ($indexedTranslatedTextItems) {
                /** @var null|TextTemplateItem $textItem */
                $textItem = $indexedTranslatedTextItems[$textItemId] ?? null;
                if ($textItem) {
                    return $textItem->resolve();
                }

                return null;
            });
        });

        return $arraysWithContents;
    }
}
