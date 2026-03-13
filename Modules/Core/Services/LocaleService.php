<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Modules\Settings\Models\Language;

/**
 * Central place to resolve the active locale for the current request.
 * Use this in every GraphQL query that returns translatable content.
 */
class LocaleService
{
    private ?string $resolved = null;

    public function get(): string
    {
        return $this->resolved ??= Language::resolveLocale();
    }

    /**
     * Translate a model attribute for the current locale.
     * Falls back to the default language if the current locale value is empty/missing.
     */
    public function trans(mixed $model, string $attribute): mixed
    {
        if (method_exists($model, 'getTranslation')) {
            return $model->getTranslation($attribute, $this->get(), false)
                ?: $model->getTranslation($attribute, Language::default()->code, false);
        }

        return $model->{$attribute};
    }

    /**
     * Shorthand to translate a single attribute on a model using the current locale,
     * with automatic fallback to the default language.
     * Equivalent to calling trans() but named for clarity at call sites.
     */
    public function t(mixed $model, string $attribute): mixed
    {
        return $this->trans($model, $attribute);
    }
}
