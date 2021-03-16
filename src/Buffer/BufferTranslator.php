<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\BufferTranslation\Buffer\KeyGenerators\KeyGenerator;
use ALI\BufferTranslation\Buffer\MessageFormat\MessageFormatsEnum;
use ALI\Translator\PhraseCollection\OriginalPhraseCollection;
use ALI\Translator\PhraseCollection\TranslatePhraseCollection;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;
use MessageFormatter;

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
        $originalPhraseCollection = new OriginalPhraseCollection($plainTranslator->getSource()->getOriginalLanguageAlias());
        $originalsCollection = (new BufferContentExtractor())->extractOriginals($bufferContent, $originalPhraseCollection);
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

        switch ($bufferContent->getMessageFormat()) {
            case MessageFormatsEnum::MESSAGE_FORMATTER:
                $parameters = [];
                if ($bufferContent->getChildContentCollection()) {
                    foreach ($bufferContent->getChildContentCollection()->getArray() as $key => $value) {
                        $parameters[$key] = $value->getContentString();
                    }
                }
                $contentString = MessageFormatter::formatMessage($translationCollection->getTranslationLanguageAlias(), $contentString, $parameters);
                break;
            case MessageFormatsEnum::BUFFER_CONTENT:

                $forReplacing = $this->prepareBufferReplacingArray($translationCollection, $childContentCollection);
                $contentString = $this->resolveChildBuffers($contentString, $forReplacing, $childContentCollection->getKeyGenerator());
                break;
        }

        return $contentString;
    }

    /**
     * @param TranslatePhraseCollection $translationCollection
     * @param BufferContentCollection $childContentCollection
     * @return array
     */
    protected function prepareBufferReplacingArray(
        TranslatePhraseCollection $translationCollection,
        BufferContentCollection $childContentCollection
    ): array
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
                if(!isset($forReplacing[$matches[0]])){
                    return $matches[0];
                }

                return $forReplacing[$matches[0]];
            },
            $contentString);

        return $contentString;
    }
}
