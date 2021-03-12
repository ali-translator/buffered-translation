<?php

namespace ALI\BufferTranslation\Buffer\KeyGenerators;

/**
 * StaticKeyGenerator
 */
class StaticKeyGenerator implements KeyGenerator
{
    /**
     * @var string
     */
    protected $keyPrefix;

    /**
     * @var string
     */
    protected $keyPostfix;

    /**
     * @param string $keyPrefix
     * @param string $keyPostfix
     */
    public function __construct($keyPrefix, $keyPostfix)
    {
        $this->keyPrefix = $keyPrefix;
        $this->keyPostfix = $keyPostfix;
    }

    /**
     * @param string $contentId
     * @return string
     */
    public function generateKey($contentId)
    {
        return $this->keyPrefix . $contentId . $this->keyPostfix;
    }

    /**
     * @return string
     */
    public function getRegularExpression()
    {
        $regDelimiter = '/';

        return '/' . preg_quote($this->keyPrefix, $regDelimiter) . '(?P<id>[-_a-zA-Z0-9]+?)' . preg_quote($this->keyPostfix, $regDelimiter) . '/';
    }

    /**
     * @return string
     */
    public function getKeyPrefix()
    {
        return $this->keyPrefix;
    }

    /**
     * @return string
     */
    public function getKeyPostfix()
    {
        return $this->keyPostfix;
    }
}
