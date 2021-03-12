<?php

namespace ALI\BufferTranslation\Buffer;

/**
 * BufferContent
 */
class BufferContent
{
    const OPTION_FORMAT = 1;
    const OPTION_WITH_CONTENT_TRANSLATION = 2;
    const OPTION_WITH_FALLBACK = 3;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var null|BufferContentCollection
     */
    protected $childContentCollection;

    /**
     * @var array
     */
    protected $options = [
        self::OPTION_FORMAT => 'string',
        self::OPTION_WITH_CONTENT_TRANSLATION => true,
        self::OPTION_WITH_FALLBACK => true,
    ];

    /**
     * @param string $content
     * @param BufferContentCollection $childContentCollection - this is for nested buffers (buffers inside buffer)
     * @param array $options
     * @param bool $withContentTranslation
     */
    public function __construct(string $content, BufferContentCollection $childContentCollection = null, $options = [])
    {
        $this->content = $content;
        $this->childContentCollection = $childContentCollection;
        $this->options = $options + $this->options;
    }

    /**
     * @return string
     */
    public function getContentString()
    {
        return $this->content;
    }

    /**
     * @return null|BufferContentCollection
     */
    public function getChildContentCollection()
    {
        return $this->childContentCollection;
    }

    /**
     * @return bool
     */
    public function isContentForTranslation()
    {
        return $this->options[self::OPTION_WITH_CONTENT_TRANSLATION];
    }

    /**
     * @return bool
     */
    public function isFallbackTranslation()
    {
        return $this->options[self::OPTION_WITH_FALLBACK];
    }
}
