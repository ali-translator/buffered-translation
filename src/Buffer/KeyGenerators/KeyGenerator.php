<?php

namespace ALI\BufferTranslation\Buffer\KeyGenerators;

/**
 * BufferKeyGenerator Interface
 */
interface KeyGenerator
{
    /**
     * @param string $contentId
     * @return string
     */
    public function generateKey($contentId);

    /**
     * @return string
     */
    public function getRegularExpression();
}
