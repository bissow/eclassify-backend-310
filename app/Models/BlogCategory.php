<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class BlogCategory extends Model
{
    use HasFactory, ManageTranslations;

    protected static function booted()
    {
        static::deleting(function ($model) {
            if ($model->seoDetail) {
                $model->seoDetail->delete();
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $appends = ['translated_name'];

    protected $with = ['translations'];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_id', 'id');
    }

    public function seoDetail(): MorphOne
    {
        return $this->morphOne(\App\Models\SeoDetail::class, 'seoable');
    }

    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function getTranslatedNameAttribute()
    {
        return $this->getTranslatedValue('name', $this->name);
    }

    public function scopeSearch($query, $search)
    {
        $originalSearch = $search;
        $search = '%' . $search . '%';
        return $query->where(function ($q) use ($search, $originalSearch) {
            $q->where('id', $originalSearch)
                ->orWhere('name', 'LIKE', $search)
                ->orWhere('slug', 'LIKE', $search)
                ->orWhereHas('translations', function ($q) use ($search) {
                    $q->where('value', 'LIKE', $search);
                });
        });
    }

    public function scopeSort($query, $column, $order)
    {
        return $query->orderBy($column, $order);
    }
}
