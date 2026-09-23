<?php

namespace App\Traits;

use App\Models\Translation;
use Illuminate\Database\Eloquent\SoftDeletes;

trait ManageTranslations
{
    use HasTranslationScopes;

    /**
     * Boot the trait.
     */
    public static function bootManageTranslations()
    {
        // Handle hard deletes (when not using soft deletes)
        static::deleting(function ($model) {
            if (!$model->usesSoftDeletes() || $model->isForceDeleting()) {
                $model->translations()->delete();
            }
        });

        // Handle force deletes (when using soft deletes)
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::forceDeleting(function ($model) {
                $model->translations()->delete();
            });
        }
    }

    /**
     * Check if model uses soft deletes
     */
    protected function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive(get_class($this)));
    }
}
