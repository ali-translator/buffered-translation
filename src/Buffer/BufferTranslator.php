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
        array $defaultBufferContentOptions
    ): TextTemplateItem
    {
        $originalPhraseCollection = new OriginalPhraseCollection($plainTranslator->getSource()->getOriginalLanguageAlias());
        $originalsCollection = (new BufferContentExtractor())->extractOriginals($textTemplateItem, $originalPhraseCollection);
        $translationCollection = $plainTranslator->translateAll($originalsCollection->getAll());
        $this->translateTextItemRecursive($textTemplateItem, $translationCollection, $defaultBufferContentOptions);

        return $textTemplateItem;
    }

    protected function translateTextItemRecursive(
        TextTemplateItem $textTemplateItem,
        TranslatePhraseCollection $translationCollection,
        array $defaultBufferContentOptions
    )
    {
        $translation = $this->getProcessesTranslation($textTemplateItem, $translationCollection, $defaultBufferContentOptions);
        $textTemplateItem->setContent($translation);
        $textTemplateItem->setCustomNotes($textTemplateItem->getCustomNotes() +
            [
                BufferContentOptions::WITH_CONTENT_TRANSLATION => false,
            ]
        );

        if ($textTemplateItem->getChildTextTemplatesCollection()) {
            foreach ($textTemplateItem->getChildTextTemplatesCollection()->getArray() as $childTextTemplateItem) {
                $this->translateTextItemRecursive($childTextTemplateItem, $translationCollection, $defaultBufferContentOptions);
            }
        }

        return $textTemplateItem;
    }

    private function getProcessesTranslation(
        TextTemplateItem $textTemplateItem,
        TranslatePhraseCollection $translationCollection,
        array $defaultBufferContentOptions
    ): string
    {
        $bufferContentOptions = $defaultBufferContentOptions + $textTemplateItem->getCustomNotes();

        $original = $textTemplateItem->getContent();
        if (!empty($bufferContentOptions[BufferContentOptions::WITH_CONTENT_TRANSLATION])) {
            $withFallback = $bufferContentOptions[BufferContentOptions::WITH_FALLBACK] ?? true;
            $translate = $translationCollection->getTranslate($textTemplateItem->getContent(), $withFallback);
        } else {
            $translate = $original;
        }

        if (!empty($bufferContentOptions[BufferContentOptions::WITH_HTML_ENCODING])) {
            $translate = htmlspecialchars($translate, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);
        }
        $modifierCallback = $bufferContentOptions[BufferContentOptions::MODIFIER_CALLBACK] ?? null;
        if ($modifierCallback) {
            $translate = $modifierCallback($translate);
        }

        return (string)$translate;
    }
}
