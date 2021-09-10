<?php

namespace ALI\BufferTranslation\Buffer\MessageFormat;

/**
 * Class
 */
class MessageFormatsEnum
{
    // allow only "plain" parameters, example "{name}"
    const BUFFER_CONTENT = 'bc';
    // uses PECL intl packet MessageFormatter::formatMessage for text formatting.
    const MESSAGE_FORMATTER = 'mf';
}
