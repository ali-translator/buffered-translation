<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\BufferTranslation\Buffer\KeyGenerators\KeyGenerator;
use ArrayIterator;
use IteratorAggregate;
use IteratorIterator;
use Traversable;

/**
 * Class
 */
class BufferContentCollection implements IteratorAggregate
{
    /**
     * @var KeyGenerator
     */
    protected $keyGenerator;

    /**
     * @var BufferContent[]
     */
    protected $buffersContent = [];

    /**
     * @var mixed[]
     */
    protected $indexedSimplyBufferContentsByContent = [];

    /**
     * @var mixed[]
     */
    protected $existBufferKeys = [];

    /**
     * @var int
     */
    protected $idIncrementValue = 0;

    /**
     * @param KeyGenerator $keyGenerator
     */
    public function __construct(KeyGenerator $keyGenerator)
    {
        $this->keyGenerator = $keyGenerator;
    }

    /**
     * @param string $content
     * @return string
     */
    public function addContent(string $content)
    {
        return $this->add(new BufferContent($content));
    }

    /**
     * Add buffer and get string buffer key
     * (after translate we replace this key two content)
     * @param BufferContent $bufferContent
     * @param string|null $bufferContentId
     * @return string
     */
    public function add(BufferContent $bufferContent, $bufferContentId = null)
    {
        if (isset($this->existBufferKeys[$bufferContent->getContentString()])) {
            // Prevent buffering exist buffered key
            return $bufferContent->getContentString();
        }

        $isSimpleBufferContent = !$bufferContent->getChildContentCollection();

        $bufferContentIdHash = $bufferContent->getIdHash();
        if (empty($bufferContentId) && $isSimpleBufferContent && isset($this->indexedSimplyBufferContentsByContent[$bufferContentIdHash])) {
            // If this text already exist, and their without parameters - return old buffer id
            $bufferContentId = $this->indexedSimplyBufferContentsByContent[$bufferContentIdHash];
        } else {
            // Adding new unique bufferContent
            $bufferContentId = $bufferContentId ?: $this->idIncrementValue++;
            $this->buffersContent[$bufferContentId] = $bufferContent;
            if ($isSimpleBufferContent) {
                $this->indexedSimplyBufferContentsByContent[$bufferContent->getIdHash()] = $bufferContentId;
            }
        }

        $bufferKey = $this->generateBufferKey($bufferContentId);
        $this->existBufferKeys[$bufferKey] = true;

        return $bufferKey;
    }

    /**
     * @param int $bufferContentId
     * @return null|BufferContent
     */
    public function getBufferContent($bufferContentId)
    {
        return !empty($this->buffersContent[$bufferContentId]) ? $this->buffersContent[$bufferContentId] : null;
    }

    /**
     * @return BufferContent[]
     */
    public function getArray()
    {
        return $this->buffersContent;
    }

    /**
     * @param int $bufferContentId
     */
    public function remove($bufferContentId)
    {
        if (isset($this->buffersContent[$bufferContentId])) {
            $buffersContent = $this->buffersContent[$bufferContentId];
            unset($this->buffersContent[$bufferContentId]);
            unset($this->indexedSimplyBufferContentsByContent[$buffersContent->getIdHash()]);
        }
    }

    /**
     * Clear buffers contents
     */
    public function clear()
    {
        $this->buffersContent = [];
    }

    /**
     * @param int $id
     * @return string
     */
    public function generateBufferKey($id)
    {
        return $this->keyGenerator->generateKey($id);
    }

    /**
     * @return IteratorIterator|Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->buffersContent);
    }

    /**
     * @return KeyGenerator
     */
    public function getKeyGenerator()
    {
        return $this->keyGenerator;
    }
}
