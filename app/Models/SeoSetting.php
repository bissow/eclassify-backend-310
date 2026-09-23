<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class SeoSetting extends Model
{
    use HasFactory, ManageTranslations;

    protected $fillable =[
         'page',
         'title',
         'description',
         'keywords',
         'image',
         'schema'
    ];
    protected $appends = ['translated_title','translated_description','translated_keywords','translated_schema'];
    public function getImageAttribute($image) {
        if (!empty($image)) {
            return url(Storage::url($image));
        }
        return $image;
    }
    public function scopeSort($query, $column, $order) {

        $query = $query->orderBy($column, $order);

        return $query->select('seo_settings.*');
    }

    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function getTranslatedTitleAttribute()
    {
        return $this->getTranslatedValue('title', $this->title);
    }

    public function getTranslatedDescriptionAttribute()
    {
        return $this->getTranslatedValue('description', $this->description);
    }

    public function getTranslatedKeywordsAttribute()
    {
        return $this->getTranslatedValue('keywords', $this->keywords);
    }

    public function getTranslatedSchemaAttribute()
    {
        return $this->getTranslatedValue('schema', $this->schema);
    }

}
