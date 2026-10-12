<?php

declare(strict_types=1);

namespace App\Localization;

use HeartPhrame\Localization\Translator;

/**
 * EN: Adds a data-only overlay without changing the framework translator or module paths.
 *
 * HR: Dodaje podatkovni overlay bez promjene framework prevoditelja ili modulskih putanja.
 */
final class RuntimeTranslator extends Translator
{
    /**
     * EN: Both bundled application locales and installed JSON locales support browser detection.
     *
     * HR: Ugrađeni aplikacijski i instalirani JSON jezici podržavaju odabir prema pregledniku.
     */
    protected function translationFileExists(string $locale): bool
    {
        if (strlen($locale) > 32 || preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) !== 1) {
            return false;
        }

        return parent::translationFileExists($locale)
        || (new RuntimeLanguageStorage($this->config->getAppRootDir()))->installed($locale);
    }

    /**
     * EN: Package translations are fallback; installed values win for exact canonical keys.
     * Missing runtime keys keep new package translations and the framework's fallback locale.
     *
     * HR: Paketni prijevodi su fallback; instalirane vrijednosti imaju prioritet za iste kanonske ključeve.
     * Nedostajući runtime ključevi zadržavaju nove paketne prijevode i frameworkov fallback jezik.
     */
    protected function loadLocale(string $locale): void
    {
        $loaded = isset($this->translations[$locale]);
        parent::loadLocale($locale);
        if (!$loaded) {
            $this->translations[$locale] = array_replace(
                $this->translations[$locale],
                (new RuntimeLanguageStorage($this->config->getAppRootDir()))->catalog($locale),
            );
        }
    }
}
