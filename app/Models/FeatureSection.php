<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureSection extends Model {
    use HasFactory, ManageTranslations;

    protected $fillable = [
        'title',
        'slug',
        'sequence',
        'filter',
        'value',
        'style',
        'min_price',
        'max_price',
        'description'
    ];
      protected $appends = ['translated_name', 'values_text'];
    public function category() {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        $query = $query->where(function ($q) use ($search) {
            $q->orWhere('title', 'LIKE', $search)
                ->orWhere('sequence', 'LIKE', $search)
                ->orWhere('filter', 'LIKE', $search)
                ->orWhere('value', 'LIKE', $search)
                ->orWhere('style', 'LIKE', $search)
                ->orWhere('min_price', 'LIKE', $search)
                ->orWhere('max_price', 'LIKE', $search)
                ->orWhere('created_at', 'LIKE', $search)
                ->orWhere('updated_at', 'LIKE', $search)
                ->orWhere('description', 'LIKE', $search);
        });
        return $query;
    }
    public function getValuesTextAttribute(): string {
        return match ($this->filter) {
            'price_criteria'    => 'Min: ' . ($this->min_price ?? 0) . ', Max: ' . ($this->max_price ?? 0),
            'category_criteria',
            'item_selection'    => $this->value ?? '',
            default             => '',
        };
    }

    public function getTranslatedNameAttribute() {
        return $this->getTranslatedValue('name', $this->title);
    }

    public function getTranslatedDescriptionAttribute() {
        return $this->getTranslatedValue('description', $this->description);
    }
}
