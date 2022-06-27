<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\BufferTranslation\Buffer\MessageFormat\MessageFormatsEnum;

/**
 * BufferContent
 */
class BufferContent
{
    const OPTION_MESSAGE_FORMAT = 1;
    const OPTION_WITH_CONTENT_TRANSLATION = 2;
    const OPTION_WITH_FALLBACK = 3;
    const OPTION_WITH_HTML_ENCODING = 4;
    const OPTION_MODIFIER_CALLBACK = 5;

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
    private $defaultOptions = [
        self::OPTION_MESSAGE_FORMAT => MessageFormatsEnum::BUFFER_CONTENT,
        self::OPTION_WITH_CONTENT_TRANSLATION => false,
        self::OPTION_WITH_FALLBACK => true,
        self::OPTION_WITH_HTML_ENCODING => false,
        self::OPTION_MODIFIER_CALLBACK => null,
    ];


    private $options = [];

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
        $this->options = $options + $this->defaultOptions;
    }

    /**
     * @return string
     */
    public function getContentString()
    {
        return $this->content;
    }

    /**
     * @var string
     */
    private $_idHash;

    public function getIdHash(): string
    {
        if (!$this->_idHash) {
            $optionsHash = null;
            if ($this->options !== $this->defaultOptions) {
                $options = $this->options;
                if (isset($options[self::OPTION_MODIFIER_CALLBACK])) {
                    $options[self::OPTION_MODIFIER_CALLBACK] = spl_object_hash($options[self::OPTION_MODIFIER_CALLBACK]);
                }
                $optionsHash = serialize($options);
            }

            $this->_idHash = $optionsHash . '#' . $this->content;
        }

        return $this->_idHash;
    }

    /**
     * @return null|BufferContentCollection
     */
    public function getChildContentCollection()
    {
        return $this->childContentCollection;
    }

    public function isContentForTranslation(): bool
    {
        return $this->options[self::OPTION_WITH_CONTENT_TRANSLATION];
    }

    public function isFallbackTranslation(): bool
    {
        return $this->options[self::OPTION_WITH_FALLBACK];
    }

    /**
     * @return int|string
     */
    public function getMessageFormat()
    {
        return $this->options[self::OPTION_MESSAGE_FORMAT];
    }

    public function isHtmlEncoding(): bool
    {
        return $this->options[self::OPTION_WITH_HTML_ENCODING];
    }

    public function getModifierCallback() :?callable
    {
        return $this->options[self::OPTION_MODIFIER_CALLBACK];
    }
}
