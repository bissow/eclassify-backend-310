<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = ['language_id', 'key', 'value', 'translatable_id', 'translatable_type'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function translatable()
    {
        return $this->morphTo();
    }

    public function getValueAttribute($value)
    {
        if (($this->attributes['key'] ?? null) !== 'value' || !is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed === '' || ($trimmed[0] !== '[' && $trimmed[0] !== '{' && $trimmed[0] !== '"')) {
            return $value;
        }

        $decoded = $value;
        for ($i = 0; $i < 5; $i++) {
            if (!is_string($decoded)) {
                break;
            }
            $next = json_decode($decoded, true);
            if ($next === null && json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            $decoded = $next;
            if (is_array($decoded)) {
                break;
            }
        }

        return is_array($decoded) ? array_values($decoded) : $value;
    }
}
