<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\Translator\PhraseCollection\OriginalPhraseCollection;

/**
 * Class
 */
class BufferContentExtractor
{
    /**
     * @param OriginalPhraseCollection $existOriginalPhraseCollection
     * @param BufferContent $bufferContent
     * @return OriginalPhraseCollection
     */
    public function extractOriginals(
        BufferContent $bufferContent,
        OriginalPhraseCollection $existOriginalPhraseCollection = null
    )
    {
        $existOriginalPhraseCollection = $existOriginalPhraseCollection ?: new OriginalPhraseCollection;
        if ($bufferContent->isContentForTranslation()) {
            $existOriginalPhraseCollection->add($bufferContent->getContentString());
        }
        if ($bufferContent->getChildContentCollection()) {
            foreach ($bufferContent->getChildContentCollection()->getArray() as $childBufferContent) {
                $this->extractOriginals($childBufferContent, $existOriginalPhraseCollection);
            }
        }

        return $existOriginalPhraseCollection;
    }
}
