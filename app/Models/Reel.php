<?php

namespace App\Models;

use App\Services\FileService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Reel extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'video',
        'thumbnail',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            if ($model->video) {
                FileService::delete($model->getRawOriginal('video'));
            }
            if ($model->thumbnail) {
                FileService::delete($model->getRawOriginal('thumbnail'));
            }
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function likes()
    {
        return $this->hasMany(ReelLike::class);
    }

    public function getVideoAttribute($value)
    {
        if (!empty($value)) {
            return url(Storage::url($value));
        }
        return $value;
    }

    public function getThumbnailAttribute($value)
    {
        if (!empty($value)) {
            return url(Storage::url($value));
        }
        return $value;
    }
}
