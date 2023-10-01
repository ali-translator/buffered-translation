<?php

namespace ALI\BufferTranslation\Tests\components\Factories;

use ALI\Translator\Source\SourceInterface;
use ALI\Translator\Source\Sources\FileSources\CsvSource\CsvFileSource;

class SourceFactory
{
    const ORIGINAL_LANGUAGE_ALIAS = 'en';
    const ORIGINAL_LANGUAGE_ISO = 'en';

    const ADDITIONAL_ORIGINAL_LANGUAGE_ALIAS = 'ru';
    const ADDITIONAL_ORIGINAL_LANGUAGE_ISO = 'ru';

    const TRANSLATION_FOR_LANGUAGE_ALIAS = 'ua';
    const TRANSLATION_FOR_LANGUAGE_ISO = 'uk';

    public function generateSource(string $originalLanguageAlias, bool $withDestroy = true): SourceInterface
    {
        $source = new CsvFileSource(SOURCE_CSV_PATH, $originalLanguageAlias);
        $this->installSource($source, $withDestroy);

        return $source;
    }

    protected function installSource(SourceInterface $source, bool $withDestroy = true): void
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
