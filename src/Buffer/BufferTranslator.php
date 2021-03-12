<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\BufferTranslation\Buffer\KeyGenerators\KeyGenerator;
use ALI\Translator\PhraseCollection\TranslatePhraseCollection;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;

/**
 * Class
 */
class BufferTranslator
{
    /**
     * @param BufferContent $bufferContent
     * @param PlainTranslatorInterface $plainTranslator
     * @return string
     */
    public function translateBuffer(
        BufferContent $bufferContent,
        PlainTranslatorInterface $plainTranslator
    )
    {
        $originalsCollection = (new BufferContentExtractor())->extractOriginals($bufferContent);
        $translationCollection = $plainTranslator->translateAll($originalsCollection->getAll());

        return $this->replaceBuffersToTranslation($bufferContent, $translationCollection);
    }

    /**
     * @param BufferContent $bufferContent
     * @param TranslatePhraseCollection $translationCollection
     * @return string
     */
    protected function replaceBuffersToTranslation(
        BufferContent $bufferContent,
        TranslatePhraseCollection $translationCollection
    )
    {
        $contentString = $bufferContent->getContentString();
        $childContentCollection = $bufferContent->getChildContentCollection();

        if ($bufferContent->isContentForTranslation()) {
            $contentString = $translationCollection->getTranslate($contentString, $bufferContent->isFallbackTranslation());
        }

        if (!$childContentCollection) {
            return $contentString;
        }

        $forReplacing = $this->prepareBufferReplacingArray($translationCollection, $childContentCollection);
        $contentString = $this->resolveChildBuffers($contentString, $forReplacing, $childContentCollection->getKeyGenerator());

        return $contentString;
    }

    /**
     * @param TranslatePhraseCollection $translationCollection
     * @param BufferContentCollection $childContentCollection
     * @return array
     */
    protected function prepareBufferReplacingArray(TranslatePhraseCollection $translationCollection, BufferContentCollection $childContentCollection): array
    {
        $forReplacing = [];
        foreach ($childContentCollection->getArray() as $bufferId => $childBufferContent) {
            $translatedChildBufferString = $this->replaceBuffersToTranslation($childBufferContent, $translationCollection);
            if (!$translatedChildBufferString && $childBufferContent->isFallbackTranslation()) {
                $translatedChildBufferString = $childBufferContent->getContentString();
            }

            $bufferKey = $childContentCollection->generateBufferKey($bufferId);
            $forReplacing[$bufferKey] = $translatedChildBufferString;
        }

        return $forReplacing;
    }

    /**
     * @param string $contentString
     * @param array $forReplacing
     * @param KeyGenerator $keyGenerator
     * @return string
     */
    protected function resolveChildBuffers(
        string $contentString,
        array $forReplacing,
        KeyGenerator $keyGenerator
    ): string
    {
        $contentString = preg_replace_callback(
            $keyGenerator->getRegularExpression(),
            function ($matches) use (&$forReplacing) {
                $replacedIds[] = $matches['id'];

                return $forReplacing[$matches[0]];
            },
            $contentString);

        return $contentString;
    }
}
