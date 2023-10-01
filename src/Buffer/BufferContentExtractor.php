<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\TextTemplate\TextTemplateItem;
use ALI\Translator\PhraseCollection\OriginalPhraseCollection;

class BufferContentExtractor
{
    public function extractOriginalsForTranslate(
        TextTemplateItem $textTemplateItem,
        OriginalPhraseCollection $originalPhraseCollection,
        ?int $bufferServiceId
    ): OriginalPhraseCollection
    {
        $customOptions = $textTemplateItem->getCustomOptions();
        if (!empty($customOptions[BufferContentOptions::ALREADY_TRANSLATED])) {
            // This "TextTemplateItem" is already translated
            return $originalPhraseCollection;
        }

        if (
            !empty($customOptions[BufferContentOptions::CREATED_BY_BUFFER_SERVICE_ID])
            && $bufferServiceId
            && $customOptions[BufferContentOptions::CREATED_BY_BUFFER_SERVICE_ID] !== $bufferServiceId
        ) {
            // This "TextTemplateItem" from another "BufferTranslation" service - skip it
            return $originalPhraseCollection;
        }

        $withTranslation = $customOptions[BufferContentOptions::WITH_CONTENT_TRANSLATION] ?? false;
        if ($withTranslation) {
            $originalPhraseCollection->add($textTemplateItem->getContent());
        }
        if ($textTemplateItem->getChildTextTemplatesCollection()) {
            foreach ($textTemplateItem->getChildTextTemplatesCollection()->getArray() as $childTextTemplate) {
                $this->extractOriginalsForTranslate($childTextTemplate, $originalPhraseCollection, $bufferServiceId);
            }
        }

        return $originalPhraseCollection;
    }
}
