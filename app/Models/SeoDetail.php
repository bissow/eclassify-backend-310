<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoDetail extends Model
{
    use HasFactory, ManageTranslations;

    protected $fillable = [
        'seoable_id',
        'seoable_type',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'schema',
    ];

    protected $appends = [
        'translated_meta_title',
        'translated_meta_description',
        'translated_meta_keywords',
        'translated_schema',
    ];

    protected $with = ['translations'];

    public function seoable()
    {
        return $this->morphTo();
    }

    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function getTranslatedMetaTitleAttribute()
    {
        return $this->getTranslatedValue('meta_title', $this->meta_title);
    }

    public function getTranslatedMetaDescriptionAttribute()
    {
        return $this->getTranslatedValue('meta_description', $this->meta_description);
    }

    public function getTranslatedMetaKeywordsAttribute()
    {
        return $this->getTranslatedValue('meta_keywords', $this->meta_keywords);
    }

    public function getTranslatedSchemaAttribute()
    {
        return $this->getTranslatedValue('schema', $this->schema);
    }
}
