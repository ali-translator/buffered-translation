<?php

namespace ALI\BufferTranslation;

use ALI\BufferTranslation\Buffer\BufferContentOptions;
use ALI\BufferTranslation\Buffer\BufferTranslator;
use ALI\BufferTranslation\Helpers\TranslatorForBufferedArray;
use ALI\TextTemplate\KeyGenerators\KeyGenerator;
use ALI\TextTemplate\KeyGenerators\StaticKeyGenerator;
use ALI\BufferTranslation\Buffer\BufferMessageFormatsEnum;
use ALI\TextTemplate\KeyGenerators\TextKeysHandler;
use ALI\TextTemplate\MessageFormat\TemplateMessageResolverFactory;
use ALI\TextTemplate\MessageFormat\TextTemplateMessageResolver;
use ALI\TextTemplate\TextTemplateFactory;
use ALI\TextTemplate\TextTemplateItem;
use ALI\TextTemplate\TextTemplatesCollection;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;

class BufferTranslation
{
    protected TextKeysHandler $textKeysHandler;
    protected TextTemplateMessageResolver $textTemplateMessageResolverForParents;
    protected TextTemplateFactory $textTemplateFactoryForChildren;
    protected BufferTranslator $bufferTranslator;

    protected PlainTranslatorInterface $plainTranslator;
    protected KeyGenerator $parentsTemplatesKeyGenerator;
    protected TextTemplatesCollection $textTemplatesCollection;

    protected array $defaultBufferContentOptions = [];

    private $bufferedKeys = [];

    public function __construct(
        PlainTranslatorInterface $plainTranslator,
        KeyGenerator             $parentsTemplatesKeyGenerator = null,
        KeyGenerator             $childrenTemplatesKeyGenerator = null,
        TextTemplatesCollection  $textTemplatesCollection = null,
        array                    $defaultBufferContentOptions = []
    )
    {
        $this->parentsTemplatesKeyGenerator = $parentsTemplatesKeyGenerator ?: new StaticKeyGenerator('{#bft-', '#}');
        $childrenTemplatesKeyGenerator = $childrenTemplatesKeyGenerator ?: new StaticKeyGenerator('{', '}');
        $this->textKeysHandler = new TextKeysHandler();
        $this->bufferTranslator = new BufferTranslator();

        $this->plainTranslator = $plainTranslator;
        $this->textTemplatesCollection = $textTemplatesCollection ?: new TextTemplatesCollection($this->parentsTemplatesKeyGenerator);

        $locale = $plainTranslator->getSource()->getOriginalLanguageAlias();
        $this->textTemplateMessageResolverForParents = new TextTemplateMessageResolver($this->parentsTemplatesKeyGenerator);
        $this->textTemplateFactoryForChildren = new TextTemplateFactory(new TemplateMessageResolverFactory($locale, $childrenTemplatesKeyGenerator));
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
        if (isset($this->bufferedKeys[$content])) {
            // We skip adding already buffered values
            return $content;
        }

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

        $textTemplateItem = $this->textTemplateFactoryForChildren->create($content, $params, $messageFormat);
        $textTemplateItem->setCustomOptions($options + [
                BufferContentOptions::WITH_CONTENT_TRANSLATION => true,
            ]
        );

        return $textTemplateItem;
    }

    public function addTextTemplateItem(TextTemplateItem $textTemplateItem): string
    {
        $textId = $this->textTemplatesCollection->add($textTemplateItem);

        $bufferKey = $this->parentsTemplatesKeyGenerator->generateKey($textId);

        $this->bufferedKeys[$bufferKey] = true;

        return $bufferKey;
    }

    public function translateBuffer(string $contentContext): string
    {
        return $this->translateBufferWithSpecificTextCollection($contentContext, $this->textTemplatesCollection);
    }

    // If you don't need to translate the entire buffer, but only the existing keys in the text
    public function translateBufferFragment(string $partOfContentContext): string
    {
        $existKeys = $this->textKeysHandler->getAllKeys($this->parentsTemplatesKeyGenerator, $partOfContentContext);
        if (!$existKeys) {
            return $partOfContentContext;
        }

        $partOfTextTemplatesCollection = $this->textTemplatesCollection->sliceByKeys($existKeys);

        return $this->translateBufferWithSpecificTextCollection($partOfContentContext, $partOfTextTemplatesCollection);
    }

    private TranslatorForBufferedArray $translatorForBufferedArray;

    /**
     * @param array|null $columnsForTranslation - null means "all string columns"
     * @param bool $isItBufferFragment - Choose whether you want to translate the entire buffer or only the existing keys in the text
     */
    public function translateArrayWithBuffers(
        array  $bufferArray,
        ?array $columnsForTranslation,
        bool   $isItBufferFragment
    ): array
    {
        if(!isset($this->translatorForBufferedArray)){
            $this->translatorForBufferedArray = new TranslatorForBufferedArray();
        }

        return $this->translatorForBufferedArray->translate(
            $bufferArray,
            $this->getTextTemplatesCollection(),
            $this->plainTranslator,
            $this->parentsTemplatesKeyGenerator,
            $columnsForTranslation,
            $isItBufferFragment,
            $this->defaultBufferContentOptions
        );
    }

    protected function translateBufferWithSpecificTextCollection(
        string                  $contentContext,
        TextTemplatesCollection $textTemplatesCollection
    )
    {
        $bufferTextTemplate = new TextTemplateItem($contentContext, $textTemplatesCollection, $this->textTemplateMessageResolverForParents);
        $bufferTextTemplate->setCustomOptions([BufferContentOptions::WITH_CONTENT_TRANSLATION => false]);

        $translatedTextTemplate = $this->bufferTranslator->translateTextTemplate(
            $bufferTextTemplate,
            $this->plainTranslator,
            $this->defaultBufferContentOptions
        );

        return $translatedTextTemplate->resolve();
    }

    public function flush(): void
    {
        $this->textTemplatesCollection->clear();
    }

    public function getPlainTranslator(): PlainTranslatorInterface
    {
        return $this->plainTranslator;
    }

    public function setPlainTranslator(PlainTranslatorInterface $plainTranslator): void
    {
        $this->plainTranslator = $plainTranslator;
    }

    public function getTextTemplatesCollection(): TextTemplatesCollection
    {
        return $this->textTemplatesCollection;
    }
}
