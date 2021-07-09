<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\BufferTranslation\Buffer\KeyGenerators\KeyGenerator;

/**
 * Class
 */
class BufferContentFactory
{
    /**
     * @var KeyGenerator
     */
    protected $keyGenerator;

    /**
     * @param KeyGenerator $keyGenerator
     */
    public function __construct(KeyGenerator $keyGenerator)
    {
        $this->keyGenerator = $keyGenerator;
    }

    public function create(string $content, array $parameters = [], array $options = [])
    {
        $bufferContentCollection = null;
        if ($parameters) {
            $bufferContentCollection = new BufferContentCollection($this->keyGenerator);
            foreach ($parameters as $childContentId => $childData) {
                if (!is_array($childData)) {
                    $childBufferContent = $this->create((string)$childData);
                } else {
                    $childContentSting = $childData['content'];
                    $childParameters = $childData['parameters'] ?? [];
                    $childOptions = $childData['options'] ?? [];
                    $childBufferContent = $this->create($childContentSting, $childParameters, $childOptions);
                }

                $bufferContentCollection->add($childBufferContent, $childContentId);
            }
        }

        return new BufferContent($content, $bufferContentCollection, $options);
    }
}
