<?php

namespace App\Models;

use App\Services\FileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ItemVideo extends Model
{
    protected $fillable = [
        'item_id',
        'video_type',
        'video_link',
        'video_file',
    ];

    protected static function booted(): void
    {
        static::deleted(static function (self $model) {
            if ($model->video_file) {
                FileService::delete($model->getRawOriginal('video_file'));
            }
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getVideoFileAttribute($value)
    {
        if (!empty($value)) {
            return url(Storage::url($value));
        }
        return $value;
    }
}
