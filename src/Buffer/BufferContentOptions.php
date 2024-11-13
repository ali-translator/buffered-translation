<?php

namespace ALI\BufferTranslation\Buffer;

use ALI\TextTemplate\TextTemplateItem;

class BufferContentOptions
{
    const WITH_CONTENT_TRANSLATION = 2;
    const WITH_FALLBACK = 3;
    const WITH_HTML_ENCODING = 4;
    const MODIFIER_CALLBACK = 5; // Callback is triggered after translation
    const CONTENT_LANGUAGE_ALIAS = 6;
    const ALREADY_TRANSLATED = 7;
    const CREATED_BY_BUFFER_SERVICE_ID = 8;
    // The callback is triggered after all children are translated and resolved
    const FINALLY_MODIFIER_CALLBACK = TextTemplateItem::OPTION_AFTER_RESOLVED_CONTENT_MODIFIER;
}
