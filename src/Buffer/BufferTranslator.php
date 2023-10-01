<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\TextTemplate\TextTemplateItem;
use ALI\Translator\PhraseCollection\OriginalPhraseCollection;
use ALI\Translator\PhraseCollection\TranslatePhraseCollection;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;

class BufferTranslator
{
    public function translateTextTemplate(
        TextTemplateItem         $textTemplateItem,
        PlainTranslatorInterface $plainTranslator,
        array $defaultChildBufferContentOptions,
        ?int $bufferServiceId
    ): TextTemplateItem
    {
        $originalPhraseCollection = new OriginalPhraseCollection($plainTranslator->getSource()->getOriginalLanguageAlias());
        $originalsCollectionForTranslation = (new BufferContentExtractor())->extractOriginalsForTranslate(
            $textTemplateItem,
            $originalPhraseCollection,
            $bufferServiceId
        );
        $translationCollection = $plainTranslator->translateAll($originalsCollectionForTranslation->getAll());
        $this->translateTextItemRecursive(
            $textTemplateItem,
            $translationCollection,
            $defaultChildBufferContentOptions,
            $bufferServiceId,
            false,
        );

        return $textTemplateItem;
    }

    protected function translateTextItemRecursive(
        TextTemplateItem $textTemplateItem,
        TranslatePhraseCollection $translationCollection,
        array $defaultBufferContentOptions,
        ?int $bufferServiceId,
        bool $useDefaultContentOptionsForParent = true
    )
    {
        $customOptions = $textTemplateItem->getCustomOptions();
        if (
            !empty($customOptions[BufferContentOptions::CREATED_BY_BUFFER_SERVICE_ID])
            && $bufferServiceId
            && $customOptions[BufferContentOptions::CREATED_BY_BUFFER_SERVICE_ID] !== $bufferServiceId
        ) {
            // This "TextTemplateItem" from another "BufferTranslation" service - skip it
            return $textTemplateItem;
        }

        [$translation, $translationLanguageAlias] = $this->getProcessesTranslation($textTemplateItem, $translationCollection, $useDefaultContentOptionsForParent ? $defaultBufferContentOptions : []);
        $textTemplateItem->setContent($translation);
        $textTemplateItem->setCustomOptions($customOptions +
            [
                BufferContentOptions::WITH_CONTENT_TRANSLATION => false,
                BufferContentOptions::CONTENT_LANGUAGE_ALIAS => $translationLanguageAlias,
                BufferContentOptions::ALREADY_TRANSLATED => true,
            ]
        );

        if ($textTemplateItem->getChildTextTemplatesCollection()) {
            foreach ($textTemplateItem->getChildTextTemplatesCollection()->getArray() as $childTextTemplateItem) {
                $this->translateTextItemRecursive($childTextTemplateItem, $translationCollection, $defaultBufferContentOptions, $bufferServiceId, true);
            }
        }

        return $textTemplateItem;
    }

    private function getProcessesTranslation(
        TextTemplateItem $textTemplateItem,
        TranslatePhraseCollection $translationCollection,
        array $defaultBufferContentOptions
    ): array
    {
        $bufferContentOptions = $textTemplateItem->getCustomOptions() + $defaultBufferContentOptions;

        $original = $textTemplateItem->getContent();
        if (!empty($bufferContentOptions[BufferContentOptions::WITH_CONTENT_TRANSLATION])) {
            $withFallback = $bufferContentOptions[BufferContentOptions::WITH_FALLBACK] ?? true;
            $translation = $translationCollection->getTranslate($textTemplateItem->getContent(), false);
            if ($translation) {
                $translationLanguageAlias = $translationCollection->getTranslationLanguageAlias();
            } else {
                $translation = $withFallback ? $original : '';
                $translationLanguageAlias = $translationCollection->getOriginalLanguageAlias();
            }
        } else {
            $translation = $original;
            $translationLanguageAlias = $translationCollection->getOriginalLanguageAlias();
        }

        if (!empty($bufferContentOptions[BufferContentOptions::WITH_HTML_ENCODING])) {
            $translation = htmlspecialchars($translation, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);
        }
        $modifierCallback = $bufferContentOptions[BufferContentOptions::MODIFIER_CALLBACK] ?? null;
        if ($modifierCallback) {
            $translation = $modifierCallback($translation);
        }

        return [(string)$translation, $translationLanguageAlias];
    }
}
