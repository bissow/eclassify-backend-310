<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

class Blog extends Model {
    use HasFactory, ManageTranslations;

    protected static function booted()
    {
        static::deleting(function ($model) {
            if ($model->seoDetail) {
                $model->seoDetail->delete();
            }
        });
    }

    protected $dates = ['created_at', 'updated_at'];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'tags',
        'category_id'
    ];
    protected $appends = [
        'translated_title',
        'translated_description',
        'translated_tags',
        'useful_count',
        'not_useful_count',
        'useful_percentage',
        'user_feedback'
    ];

    public function category() {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function getImageAttribute($image) {
        if (!empty($image)) {
            return url(Storage::url($image));
        }
        return $image;
    }

        public function getTagsAttribute($value) {
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                return explode(',', $value);
            }

            return [];
        }


    public function setTagsAttribute($value) {
        if (is_array($value)) {
            $cleaned = array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), $value);
            $this->attributes['tags'] = implode(',', $cleaned);
        } elseif (is_string($value)) {
            $this->attributes['tags'] = trim($value, " \t\n\r\0\x0B\"'");
        } else {
            $this->attributes['tags'] = '';
        }
    }



    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function seoDetail(): MorphOne
    {
        return $this->morphOne(\App\Models\SeoDetail::class, 'seoable');
    }
    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        $query = $query->where(function ($q) use ($search) {
            $q->orWhere('title', 'LIKE', $search)
                ->orWhere('description', 'LIKE', $search)
                ->orWhere('tags', 'LIKE', $search);
        });
        return $query;
    }

    public function scopeSort($query, $column, $order) {
        if ($column == "category.name" || $column == "category_name") {
            return $query->leftJoin('blog_categories', 'blog_categories.id', '=', 'blogs.category_id')
                ->orderBy('blog_categories.name', $order)
                ->select('blogs.*');
        }
        return $query->orderBy($column, $order);
    }
    public function getTranslatedTitleAttribute()
    {
        return $this->getTranslatedValue('title', $this->title);
    }

    public function getTranslatedTagsAttribute()
    {
        $translatedTags = $this->getTranslatedValue('tags', null);

        if (!empty($translatedTags)) {
            if (is_array($translatedTags)) {
                return array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), $translatedTags);
            }

            if (is_string($translatedTags)) {
                return array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), explode(',', $translatedTags));
            }
        }

        return array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), $this->tags ?? []);
    }

    public function getTranslatedDescriptionAttribute()
    {
        return $this->getTranslatedValue('description', $this->description);
    }

    public function feedbacks() {
        return $this->hasMany(BlogFeedback::class, 'blog_id');
    }

    public function getUsefulCountAttribute() {
        if (array_key_exists('useful_count', $this->attributes)) {
            return $this->attributes['useful_count'];
        }
        return $this->feedbacks()->where('is_useful', 1)->count();
    }

    public function getNotUsefulCountAttribute() {
        if (array_key_exists('not_useful_count', $this->attributes)) {
            return $this->attributes['not_useful_count'];
        }
        return $this->feedbacks()->where('is_useful', 0)->count();
    }

    public function getUsefulPercentageAttribute() {
        $useful = $this->useful_count;
        $notUseful = $this->not_useful_count;
        $total = $useful + $notUseful;
        return $total > 0 ? round(($useful / $total) * 100, 2) : 0;
    }

    public function getUserFeedbackAttribute() {
        $user = request()->user('sanctum');
        if ($user) {
            if ($this->relationLoaded('user_feedback')) {
                $userFeedBack = $this->user_feedback()->first();
                return !empty($userFeedBack) ? $userFeedBack->is_useful : null;
            }
            $feedback = $this->feedbacks()->where('user_id', $user->id)->first();
            return $feedback ? $feedback->is_useful : null;
        }
        return null;
    }

    public function scopeActiveCategory($query) {
        $query->whereHas('category', function($subQuery){
            $subQuery->where('is_active', 1);
        });
    }

}
