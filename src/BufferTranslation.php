<?php

namespace ALI\BufferTranslation;

use ALI\BufferTranslation\Buffer\BufferContentOptions;
use ALI\BufferTranslation\Buffer\BufferTranslator;
use ALI\BufferTranslation\Helpers\TranslatorForBufferedArray;
use ALI\TextTemplate\TemplateResolver\Template\KeyGenerators\KeyGenerator;
use ALI\TextTemplate\TemplateResolver\Template\KeyGenerators\StaticKeyGenerator;
use ALI\BufferTranslation\Buffer\BufferMessageFormatsEnum;
use ALI\TextTemplate\TemplateResolver\Template\KeyGenerators\TextKeysHandler;
use ALI\TextTemplate\TemplateResolver\Template\LogicVariables\Handlers\DefaultHandlers\DefaultHandlersFacade;
use ALI\TextTemplate\TemplateResolver\Template\LogicVariables\Handlers\HandlersRepository;
use ALI\TextTemplate\TemplateResolver\Template\LogicVariables\LogicVariableParser;
use ALI\TextTemplate\TemplateResolver\TemplateMessageResolverFactory;
use ALI\TextTemplate\TemplateResolver\Template\TextTemplateMessageResolver;
use ALI\TextTemplate\TextTemplateFactory;
use ALI\TextTemplate\TextTemplateItem;
use ALI\TextTemplate\TextTemplatesCollection;
use ALI\Translator\Languages\Language;
use ALI\Translator\Languages\LanguageRepositoryInterface;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;

class BufferTranslation
{
    static int $autoIncrementIdKey = 1;

    protected int $serviceId;
    protected TextKeysHandler $textKeysHandler;
    protected TextTemplateMessageResolver $textTemplateMessageResolverForParents;
    protected TextTemplateFactory $textTemplateFactoryForChildren;
    protected BufferTranslator $bufferTranslator;

    protected PlainTranslatorInterface $plainTranslator;
    protected KeyGenerator $parentsTemplatesKeyGenerator;
    protected TextTemplatesCollection $textTemplatesCollection;

    protected array $defaultBufferContentOptions = [];
    private array $bufferedKeysWithTemplateIds = [];

    protected Language $originalLanguage;
    protected Language $translationLanguage;

    public function __construct(
        PlainTranslatorInterface $plainTranslator,
        LanguageRepositoryInterface $languageRepository,
        KeyGenerator             $parentsTemplatesKeyGenerator = null,
        KeyGenerator             $childrenTemplatesKeyGenerator = null,
        TextTemplatesCollection  $textTemplatesCollection = null,
        array                    $defaultBufferContentOptions = [],
        ?HandlersRepository $customLogicVariableHandlersRepository = null
    )
    {
        $this->serviceId = static::$autoIncrementIdKey++;

        $this->parentsTemplatesKeyGenerator = $parentsTemplatesKeyGenerator ?: new StaticKeyGenerator('{#bft-', '#}');
        $childrenTemplatesKeyGenerator = $childrenTemplatesKeyGenerator ?: new StaticKeyGenerator('{', '}');
        $this->textKeysHandler = new TextKeysHandler();
        $this->bufferTranslator = new BufferTranslator();

        $this->plainTranslator = $plainTranslator;
        $this->textTemplatesCollection = $textTemplatesCollection ?: new TextTemplatesCollection();

        // Get languages ISO
        $originalLanguageAlias = $plainTranslator->getSource()->getOriginalLanguageAlias();
        $this->originalLanguage = $languageRepository->find($originalLanguageAlias);
        $originalLanguageISO = $this->originalLanguage->getIsoCode();
        $translationLanguageAlias = $plainTranslator->getTranslationLanguageAlias();
        $this->translationLanguage = $languageRepository->find($translationLanguageAlias);
        $translationLanguageISO = $this->translationLanguage->getIsoCode();

        // Resolver for "root" templates
        $this->textTemplateMessageResolverForParents = new TextTemplateMessageResolver(
            $this->parentsTemplatesKeyGenerator,
            new HandlersRepository(), // in parent level no handlers we need
            new LogicVariableParser()
        );

        // Resolver for "children" templates
        $logicVariableHandlersRepository = (new DefaultHandlersFacade())->registerHandlers(
            $customLogicVariableHandlersRepository ?: new HandlersRepository(),
            [$translationLanguageISO, $originalLanguageISO] // "$translationLanguageISO" -  main language, "$originalLanguageISO" - for "fallbacks" (if original has no translations)
        );
        $this->textTemplateFactoryForChildren = new TextTemplateFactory(new TemplateMessageResolverFactory(
            $translationLanguageISO,
            $childrenTemplatesKeyGenerator,
            $logicVariableHandlersRepository
        ));

        // These options are used and set in TextTemplatesItem only before translation
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
        if (isset($this->bufferedKeysWithTemplateIds[$content])) {
            // Skip adding already buffered values
            return $content;
        }

        $textTemplateItem = $this->createTextTemplateItem($content, $params, $options, $messageFormat);
        if (!$textTemplateItem) {
            return '';
        }

        return $this->addTextTemplateItem($textTemplateItem);
    }

    public function createAndAddTextTemplateItem(
        ?string $content,
        array   $params = [],
        array   $options = [],
        string  $messageFormat = BufferMessageFormatsEnum::TEXT_TEMPLATE
    ): ?TextTemplateItem
    {
        $textTemplateItem = $this->createTextTemplateItem($content, $params, $options, $messageFormat);
        if ($textTemplateItem) {
            $this->addTextTemplateItem($textTemplateItem);
        }

        return $textTemplateItem;
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
                BufferContentOptions::CREATED_BY_BUFFER_SERVICE_ID => $this->serviceId,
                BufferContentOptions::WITH_CONTENT_TRANSLATION => true,
            ]
        );

        return $textTemplateItem;
    }

    public function addTextTemplateItem(TextTemplateItem &$textTemplateItem): string
    {
        $textId = $this->textTemplatesCollection->add($textTemplateItem);
        // perhaps the id of an existing object was returned to us,
        // so we need to get the required object from the storage by this id
        $textTemplateItem = $this->textTemplatesCollection->get($textId);

        $bufferKey = $this->parentsTemplatesKeyGenerator->generateKey($textId);

        $this->bufferedKeysWithTemplateIds[$bufferKey] = $textId;

        return $bufferKey;
    }

    public function translateBuffer(string $contentContext): string
    {
        return $this->translateBufferWithSpecificTextCollection($contentContext, $this->textTemplatesCollection);
    }

    // If you don't need to translate the entire buffer, but only the keys that exist in the provided text
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

    public function getOriginalLanguage(): Language
    {
        return $this->originalLanguage;
    }

    public function getTranslationLanguage(): Language
    {
        return $this->translationLanguage;
    }

    protected function translateBufferWithSpecificTextCollection(
        string                  $contentContext,
        TextTemplatesCollection $textTemplatesCollection
    )
    {
        $bufferTextTemplate = new TextTemplateItem($contentContext, $textTemplatesCollection, $this->textTemplateMessageResolverForParents);
        $translatedBufferTextTemplate = $this->translateBufferTextTemplate($bufferTextTemplate);

        return $translatedBufferTextTemplate->resolve();
    }

    // It can be useful when you use several buffers, and there is an overlap in the use of templates of one buffer by another
    // In this case, you can translate all the templates of all the buffers, and "resolve" only one of them.
    public function preTranslateAllInsideTextTemplates(): void
    {
        $bufferTextTemplate = new TextTemplateItem('', $this->textTemplatesCollection, $this->textTemplateMessageResolverForParents);
        $this->translateBufferTextTemplate($bufferTextTemplate);
    }

    protected function translateBufferTextTemplate(TextTemplateItem $bufferTextTemplate): TextTemplateItem
    {
        $bufferTextTemplate->setCustomOptions([BufferContentOptions::WITH_CONTENT_TRANSLATION => false]);

        return $this->bufferTranslator->translateTextTemplate(
            $bufferTextTemplate,
            $this->plainTranslator,
            $this->defaultBufferContentOptions,
            $this->serviceId
        );
    }

    public function flush(): void
    {
        $this->bufferedKeysWithTemplateIds = [];
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

    public function getTextTemplateItemByBufferKey(string $bufferKey): ?TextTemplateItem
    {
        $templateId = $this->bufferedKeysWithTemplateIds[$bufferKey] ?? null;
        if ($templateId === null) {
            return null;
        }

        return $this->textTemplatesCollection->get((string)$templateId);
    }

    public function getTextTemplatesCollection(): TextTemplatesCollection
    {
        return $this->textTemplatesCollection;
    }
}
