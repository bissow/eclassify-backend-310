<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatTemplate extends Model {
    use HasFactory, ManageTranslations;

    protected $fillable = [
        'name',
        'is_global',
        'status',
        'created_by',
    ];

    protected $appends = ['translated_name'];

    public function translations() {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function chat_template_category() {
        return $this->hasMany(ChatTemplateCategory::class, 'chat_template_id');
    }

    public function categories() {
        return $this->belongsToMany(Category::class, ChatTemplateCategory::class);
    }

    public function questions() {
        return $this->hasMany(ChatTemplateQuestion::class)->orderBy('sequence');
    }

    public function customerQuestions() {
        return $this->hasMany(ChatTemplateQuestion::class)->where('role', 'customer')->orderBy('sequence');
    }

    public function sellerQuestions() {
        return $this->hasMany(ChatTemplateQuestion::class)->where('role', 'seller')->orderBy('sequence');
    }

    public function getTranslatedNameAttribute() {
        return $this->getTranslatedValue('name', $this->name);
    }

    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        return $query->where(function ($q) use ($search) {
            $q->orWhere('name', 'LIKE', $search)
                ->orWhereHas('categories', function ($q) use ($search) {
                    $q->where('name', 'LIKE', $search);
                });
        });
    }

    public function scopeFilter($query, $filterObject) {
        if (!empty($filterObject)) {
            foreach ($filterObject as $column => $value) {
                if ($column == "category_names") {
                    $query->whereHas('chat_template_category', function ($query) use ($value) {
                        $query->where('category_id', $value);
                    });
                } else {
                    $query->where((string)$column, (string)$value);
                }
            }
        }
        return $query;
    }
}
