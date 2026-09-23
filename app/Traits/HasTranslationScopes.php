<?php

namespace App\Traits;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;

/**
 * Trait HasTranslationScopes
 *
 * Provides query scopes and helper methods for filtering translations by language.
 * This trait should be used on models that have a morphMany relationship with Translation model.
 */
trait HasTranslationScopes
{
    /**
     * Get the current language ID from the Content-Language header
     *
     * @return int|null
     */
    public static function getCurrentLanguageId(): ?int
    {
        $languageCode = request()->header('Content-Language') ?? null;

        if (empty($languageCode)) {
            return null;
        }

        // Cache language ID lookup to avoid repeated database queries
        return Cache::remember("language_id_{$languageCode}", 3600, function () use ($languageCode) {
            return Language::where('code', $languageCode)->value('id');
        });
    }

    /**
     * Scope to filter translations by current language from header
     *
     * Usage: Category::withCurrentLanguageTranslations()->get()
     */
    public function scopeWithCurrentLanguageTranslations($query)
    {
        $languageId = static::getCurrentLanguageId();

        if (!$languageId) {
            return $query->with('translations');
        }

        return $query->with(['translations' => function ($q) use ($languageId) {
            $q->where('language_id', $languageId);
        }]);
    }

    /**
     * Scope to search in translations based on current language
     *
     * Usage: Category::searchInTranslations('name', 'search term')->get()
     */
    public function scopeSearchInTranslations($query, string $key, string $searchTerm)
    {
        $languageId = static::getCurrentLanguageId();

        return $query->whereHas('translations', function ($q) use ($key, $searchTerm, $languageId) {
            $q->where('key', $key)
              ->where('value', 'like', '%' . $searchTerm . '%');

            if ($languageId) {
                $q->where('language_id', $languageId);
            }
        });
    }

    /**
     * Scope to search in any translation value based on current language
     *
     * Usage: Category::searchInAnyTranslation('search term')->get()
     */
    public function scopeSearchInAnyTranslation($query, string $searchTerm)
    {
        $languageId = static::getCurrentLanguageId();

        return $query->whereHas('translations', function ($q) use ($searchTerm, $languageId) {
            $q->where('value', 'like', '%' . $searchTerm . '%');

            if ($languageId) {
                $q->where('language_id', $languageId);
            }
        });
    }

    /**
     * Get translated value for a specific key
     *
     * @param string $key The translation key
     * @param mixed $defaultValue The default value if translation not found
     * @return mixed
     */
    public function getTranslatedValue(string $key, $defaultValue = null)
    {
        $languageId = static::getCurrentLanguageId();

        if (!$languageId) {
            return $defaultValue;
        }

        // Use loaded translations if available (check it's a collection, not a string column)
        if ($this->relationLoaded('translations') && $this->getRelation('translations') instanceof \Illuminate\Support\Collection) {
            $translation = $this->getRelation('translations')
                ->where('language_id', $languageId)
                ->where('key', $key)
                ->first();

            return $translation?->value ?? $defaultValue;
        }

        // Otherwise query the database
        $translation = $this->translations()
            ->where('language_id', $languageId)
            ->where('key', $key)
            ->first();

        return $translation?->value ?? $defaultValue;
    }

    /**
     * Scope to filter related translations by current language
     *
     * Usage: Item::with(['category' => fn($q) => $q->withLanguageTranslations()])->get()
     */
    public function scopeWithLanguageTranslations($query)
    {
        $languageId = static::getCurrentLanguageId();

        if (!$languageId) {
            return $query->with('translations');
        }

        return $query->with(['translations' => function ($q) use ($languageId) {
            $q->where('language_id', $languageId);
        }]);
    }
}
