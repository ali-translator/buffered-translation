<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\Translator\PhraseCollection\OriginalPhraseCollection;

/**
 * Class
 */
class BufferContentExtractor
{
    /**
     * @param OriginalPhraseCollection $originalPhraseCollection
     * @param BufferContent $bufferContent
     * @return OriginalPhraseCollection
     */
    public function extractOriginals(
        BufferContent $bufferContent,
        OriginalPhraseCollection $originalPhraseCollection
    )
    {
        if ($bufferContent->isContentForTranslation()) {
            $originalPhraseCollection->add($bufferContent->getContentString());
        }
        if ($bufferContent->getChildContentCollection()) {
            foreach ($bufferContent->getChildContentCollection()->getArray() as $childBufferContent) {
                $this->extractOriginals($childBufferContent, $originalPhraseCollection);
            }
        }

        return $originalPhraseCollection;
    }
}
