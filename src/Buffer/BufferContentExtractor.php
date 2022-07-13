<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\TextTemplate\TextTemplateItem;
use ALI\Translator\PhraseCollection\OriginalPhraseCollection;

class BufferContentExtractor
{
    public function extractOriginals(
        TextTemplateItem $textTemplateItem,
        OriginalPhraseCollection $originalPhraseCollection
    ): OriginalPhraseCollection
    {
        $withTranslation = $textTemplateItem->getCustomNotes()[BufferContentOptions::WITH_CONTENT_TRANSLATION] ?? false;
        if ($withTranslation) {
            $originalPhraseCollection->add($textTemplateItem->getContent());
        }
        if ($textTemplateItem->getChildTextTemplatesCollection()) {
            foreach ($textTemplateItem->getChildTextTemplatesCollection()->getArray() as $childTextTemplate) {
                $this->extractOriginals($childTextTemplate, $originalPhraseCollection);
            }
        }

        return $originalPhraseCollection;
    }
}
