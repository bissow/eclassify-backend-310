<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tip extends Model {
    use HasFactory, SoftDeletes, ManageTranslations;

    protected $fillable = [
        'description'
    ];
    protected $appends = ['translated_name'];

    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        return $query->where(function ($q) use ($search) {
            $q->orWhere('description', 'LIKE', $search)
                ->orWhereHas('translations', function ($q) use ($search) {
                    $q->where('key', 'description')->where('value', 'LIKE', $search);
                });
        });
    }

    public function getTranslatedNameAttribute()
    {
        return $this->getTranslatedValue('description', $this->description);
    }
}
