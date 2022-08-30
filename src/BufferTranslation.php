<?php

namespace ALI\BufferTranslation;

use ALI\BufferTranslation\Buffer\BufferContentOptions;
use ALI\BufferTranslation\Buffer\BufferTranslator;
use ALI\TextTemplate\KeyGenerators\KeyGenerator;
use ALI\TextTemplate\KeyGenerators\StaticKeyGenerator;
use ALI\BufferTranslation\Buffer\BufferMessageFormatsEnum;
use ALI\TextTemplate\TextTemplateFactory;
use ALI\TextTemplate\TextTemplateItem;
use ALI\TextTemplate\TextTemplateResolver;
use ALI\TextTemplate\TextTemplatesCollection;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;

class BufferTranslation
{
    protected PlainTranslatorInterface $plainTranslator;

    protected TextTemplatesCollection $textTemplatesCollection;

    protected TextTemplateFactory $textTemplateFactory;

    protected array $defaultBufferContentOptions = [];

    public function __construct(
        PlainTranslatorInterface $plainTranslator,
        KeyGenerator             $templatesKeyGenerator = null,
        KeyGenerator             $childTemplatesKeyGenerator = null,
        TextTemplatesCollection  $textTemplatesCollection = null,
        array                    $defaultBufferContentOptions = []
    )
    {
        $templatesKeyGenerator = $templatesKeyGenerator ?: new StaticKeyGenerator('{#bft-', '#}');
        $childTemplatesKeyGenerator = $childTemplatesKeyGenerator ?: new StaticKeyGenerator('{', '}');

        $this->plainTranslator = $plainTranslator;
        $this->textTemplatesCollection = $textTemplatesCollection ?: new TextTemplatesCollection($templatesKeyGenerator);
        $this->textTemplateFactory = new TextTemplateFactory($childTemplatesKeyGenerator);
        $this->defaultBufferContentOptions = [
                BufferContentOptions::WITH_FALLBACK => true,
            ] + $defaultBufferContentOptions;
    }

    public function add(
        ?string $content,
        array   $params = [],
        array   $options = [],
        string  $messageFormat = BufferMessageFormatsEnum::TEXT_TEMPLATE
    ): string
    {
        $textTemplateItem = $this->createTextTemplateItem($content, $params, $options, $messageFormat);
        if (!$textTemplateItem) {
            return '';
        }

        return $this->addTextTemplateItem($textTemplateItem);
    }

    public function createTextTemplateItem(
        ?string $content,
        array   $params = [],
        array   $options = [],
        string  $messageFormat = BufferMessageFormatsEnum::TEXT_TEMPLATE
    ): ?TextTemplateItem
    {
        if (!$content) {
            return null;
        }

        $textTemplateItem = $this->textTemplateFactory->create($content, $params, $messageFormat);
        $textTemplateItem->setCustomNotes($options + [
                BufferContentOptions::WITH_CONTENT_TRANSLATION => true,
            ]
        );

        return $textTemplateItem;
    }

    public function addTextTemplateItem(TextTemplateItem $textTemplateItem): string
    {
        return $this->textTemplatesCollection->add($textTemplateItem);
    }

    public function translateBuffer(string $contentContext): string
    {
        $bufferTextTemplate = new TextTemplateItem($contentContext, $this->textTemplatesCollection);
        $bufferTextTemplate->setCustomNotes([BufferContentOptions::WITH_CONTENT_TRANSLATION => false]);

        $bufferTranslate = new BufferTranslator();
        $bufferTextTemplate = $bufferTranslate->translateTextTemplate(
            $bufferTextTemplate,
            $this->plainTranslator,
            $this->defaultBufferContentOptions
        );

        $textTemplateResolver = new TextTemplateResolver($this->plainTranslator->getTranslationLanguageAlias());

        return $textTemplateResolver->resolve($bufferTextTemplate);
    }

    public function flush(): void
    {
        $this->textTemplatesCollection = new TextTemplatesCollection($this->textTemplatesCollection->getKeyGenerator());
    }

    public function getPlainTranslator(): PlainTranslatorInterface
    {
        return $this->plainTranslator;
    }
}
