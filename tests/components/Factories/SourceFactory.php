<?php

namespace ALI\BufferTranslation\Tests\components\Factories;

use ALI\Translator\Source\SourceInterface;
use ALI\Translator\Source\Sources\FileSources\CsvSource\CsvFileSource;

/**
 * SourceFactory
 */
class SourceFactory
{
    const ORIGINAL_LANGUAGE_ALIAS = 'en';
    const CURRENT_LANGUAGE_ALIAS = 'ua';

    /**
     * @param $originalLanguageAlias
     * @param $sourceType
     * @param bool $withDestroy
     * @return CsvFileSource
     */
    public function generateSource($originalLanguageAlias, $withDestroy = true)
    {
        $source = new CsvFileSource(SOURCE_CSV_PATH, $originalLanguageAlias);
        $this->installSource($source, $withDestroy);

        return $source;
    }

    /**
     * @param SourceInterface $source
     * @param $withDestroy
     * @return SourceInterface
     */
    public function regenerateSource($source, $withDestroy)
    {
        return $this->generateSource($source->getOriginalLanguageAlias(), $withDestroy);
    }

    protected function installSource(SourceInterface $source, $withDestroy = true)
    {
        $sourceInstaller = $source->generateInstaller();
        $needInstall = true;
        if ($sourceInstaller->isInstalled()) {
            if ($withDestroy) {
                $sourceInstaller->destroy();
            } else {
                $needInstall = false;
            }
        }
        if ($needInstall) {
            $sourceInstaller->install();
        }
    }
}
