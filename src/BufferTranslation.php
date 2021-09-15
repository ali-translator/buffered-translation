<?php

namespace ALI\BufferTranslation;

use ALI\BufferTranslation\Buffer\BufferContent;
use ALI\BufferTranslation\Buffer\BufferContentCollection;
use ALI\BufferTranslation\Buffer\BufferContentFactory;
use ALI\BufferTranslation\Buffer\BufferTranslator;
use ALI\BufferTranslation\Buffer\KeyGenerators\KeyGenerator;
use ALI\BufferTranslation\Buffer\KeyGenerators\StaticKeyGenerator;
use ALI\Translator\PlainTranslator\PlainTranslatorInterface;

/**
 * Class
 */
class BufferTranslation
{
    /**
     * @var PlainTranslatorInterface
     */
    protected $plainTranslator;

    /**
     * @var BufferContentCollection
     */
    protected $bufferContentCollection;

    /**
     * @var BufferContentFactory
     */
    protected $bufferContentFactory;

    /**
     * @var array
     */
    protected $defaultBufferContentOptions = [];

    public function __construct(
        PlainTranslatorInterface $plainTranslator,
        KeyGenerator $templatesKeyGenerator = null,
        KeyGenerator $childTemplatesKeyGenerator = null,
        BufferContentCollection $bufferContentCollection = null,
        array $defaultBufferContentOptions = []
    )
    {
        $templatesKeyGenerator = $templatesKeyGenerator ?: new StaticKeyGenerator('{#bft-', '#}');
        $childTemplatesKeyGenerator = $childTemplatesKeyGenerator ?: new StaticKeyGenerator('{', '}');

        $this->plainTranslator = $plainTranslator;
        $this->bufferContentCollection = $bufferContentCollection ?: new BufferContentCollection($templatesKeyGenerator);
        $this->bufferContentFactory = new BufferContentFactory($childTemplatesKeyGenerator);
        $this->defaultBufferContentOptions = [
                BufferContent::OPTION_WITH_CONTENT_TRANSLATION => true,
                BufferContent::OPTION_WITH_FALLBACK=> true,
            ] + $defaultBufferContentOptions;
    }

    public function add(?string $content, array $params = [], array $options = []): string
    {
        if (!$content) {
            return '';
        }
        $options += $this->defaultBufferContentOptions;
        $bufferContent = $this->bufferContentFactory->create($content, $params, $options);

        return $this->addBuffer($bufferContent);
    }

    /**
     * @param BufferContent $bufferContent
     * @return string
     */
    public function addBuffer(BufferContent $bufferContent)
    {
        return $this->bufferContentCollection->add($bufferContent);
    }

    /**
     * @param string $contentContext
     * @return string
     * @throws \Exception
     */
    public function translateBuffer($contentContext)
    {
        $bufferContent = new BufferContent($contentContext, $this->bufferContentCollection, [
            BufferContent::OPTION_WITH_CONTENT_TRANSLATION => false,
        ]);
        $bufferTranslate = new BufferTranslator();

        return $bufferTranslate->translateBuffer($bufferContent, $this->plainTranslator);
    }

    /**
     * @return PlainTranslatorInterface
     */
    public function getPlainTranslator()
    {
        return $this->plainTranslator;
    }
}
