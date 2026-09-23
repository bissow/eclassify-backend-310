<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class EditedImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'edited_images';

    protected $fillable = [
        'user_id',
        'item_id',
        'original_path',
        'edited_path',
        'transformations',
        'disk',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'transformations' => 'array',
        'file_size' => 'integer',
    ];

    protected $appends = [
        'original_url',
        'edited_url',
    ];

    /**
     * User who performed the edit.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Item / Ad the edited image belongs to.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get full public URL of the original image.
     */
    public function getOriginalUrlAttribute(): ?string
    {
        if (empty($this->original_path)) {
            return null;
        }

        if (filter_var($this->original_path, FILTER_VALIDATE_URL)) {
            return $this->original_path;
        }

        $disk = $this->disk ?: config('filesystems.default', 'public');
        return url(Storage::disk($disk)->url($this->original_path));
    }

    /**
     * Get full public URL of the edited image.
     */
    public function getEditedUrlAttribute(): ?string
    {
        if (empty($this->edited_path)) {
            return null;
        }

        if (filter_var($this->edited_path, FILTER_VALIDATE_URL)) {
            return $this->edited_path;
        }

        $disk = $this->disk ?: config('filesystems.default', 'public');
        return url(Storage::disk($disk)->url($this->edited_path));
    }
}
