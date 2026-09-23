<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Category extends Model
{
    use HasFactory, HasRecursiveRelationships, ManageTranslations;

    public function getPathName()
    {
        return 'tree_path';
    }

    protected static function booted()
    {
        static::deleting(function ($model) {
            if ($model->seoDetail) {
                $model->seoDetail->delete();
            }
        });

        static::saving(function ($model) {
            if (!empty($model->slug)) {
                $model->path = $model->buildPath();
            }
        });

        static::saved(function ($model) {
            if ($model->wasChanged(['slug', 'parent_category_id'])) {
                $model->subcategories()->get()->each(function ($child) {
                    $child->path = $child->buildPath();
                    $child->saveQuietly();
                    $child->cascadePathToDescendants();
                });
            }
        });
    }

    protected $fillable = [
        'name',
        'parent_category_id',
        'image',
        'slug',
        'path',
        'status',
        'description',
        'is_job_category',
        'price_optional',
        'is_featured'
    ];

    public function buildPath(): string
    {
        $parent = $this->parent_category_id ? static::find($this->parent_category_id) : null;
        $prefix = ($parent && $parent->path) ? $parent->path : '/ads';
        return rtrim($prefix, '/') . '/' . $this->slug;
    }

    public function cascadePathToDescendants(): void
    {
        $this->subcategories()->get()->each(function ($child) {
            $child->path = $child->buildPath();
            $child->saveQuietly();
            $child->cascadePathToDescendants();
        });
    }

    public function getParentKeyName()
    {
        return 'parent_category_id';
    }

    protected $appends = ['translated_name'];

    protected $with = ['translations'];

    public function subcategories()
    {
        return $this->hasMany(self::class, 'parent_category_id');
    }

    public function custom_fields()
    {
        return $this->hasMany(CustomFieldCategory::class);
    }

    public function getImageAttribute($image)
    {
        if (! empty($image)) {
            return url(Storage::url($image));
        }

        return $image;
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function packages(){
        return $this->hasMany(PackageCategory::class, 'category_id')->where('is_discontinued','!=',1);
    }

    public function approved_items()
    {
        return $this->hasMany(Item::class)->where('status', 'approved');
    }

    public function getAllItemsCountAttribute()
    {
        // Count items of current category (no translations involved)
        $totalItems = $this->items()
            ->where('status', 'approved')
            ->getNonExpiredItems()
            ->count();

        // Load subcategories WITHOUT translations (only here)
        $subcategories = $this->subcategories()
            ->without('translations')
            ->get();

        foreach ($subcategories as $subcategory) {
            $totalItems += $subcategory->all_items_count;
        }

        return $totalItems;
    }

    public function scopeSearch($query, $search)
    {
        $originalSearch = $search;
        $search = '%'.$search.'%';

        return $query->where(function ($q) use ($search, $originalSearch) {
            $q->where('id', $originalSearch)
                ->orWhere('name', 'LIKE', $search)
                ->orWhereHas('translations', function ($q) use ($search) {
                    $q->where('value', 'LIKE', $search);
                });
        });
    }

    public function slider(): MorphOne
    {
        return $this->morphOne(Slider::class, 'model');
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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    public function getFullPathAttribute()
    {
        $names = [];
        $current = $this;
        $visited = [];

        while ($current) {
            if (in_array($current->id, $visited, true)) {
                break; // prevent loop
            }
            $visited[] = $current->id;

            $names[] = $current->name;
            $current = $current->parent;
        }

        return implode(' > ', array_reverse($names));
    }

    public function getItemsGroupedByStatusAttribute()
    {
        $counts = [];

        // Count items in this category
        $items = $this->items()->get();
        foreach ($items as $item) {
            $counts[$item->status] = ($counts[$item->status] ?? 0) + 1;
        }

        // Include subcategories recursively
        foreach ($this->subcategories as $subcategory) {
            $subCounts = $subcategory->items_grouped_by_status;
            foreach ($subCounts as $status => $count) {
                $counts[$status] = ($counts[$status] ?? 0) + $count;
            }
        }

        return $counts;
    }

    public function getOtherItemsCountAttribute()
    {
        $totalItems = $this->items()->where('status', '!=', 'approved')->count();
        foreach ($this->subcategories as $subcategory) {
            $totalItems += $subcategory->other_items_count;
        }

        return $totalItems;
    }
}
