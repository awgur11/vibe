<?php

namespace App\Services;

use Dejurin\GoogleTranslateForFree;
use RuntimeException;

class TranslationService
{
    public function __construct(private GoogleTranslateForFree $translator) {}

    public function translate(string $text, string $sourceLocale, string $targetLocale): string
    {
        if ($text === '' || $sourceLocale === $targetLocale) {
            return $text;
        }

        $translation = $this->translator->translate($sourceLocale, $targetLocale, $text, 5);

        if (! is_string($translation) || $translation === '') {
            throw new RuntimeException('Google Translate did not return a translation.');
        }

        return $translation;
    }
}
