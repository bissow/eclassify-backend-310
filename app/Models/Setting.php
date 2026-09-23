<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Str;

class Setting extends Model
{
    use HasFactory, ManageTranslations;

    public $table = 'settings';

    protected $fillable = [
        'name',
        'value',
        'type',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public static function getValue(string $name)
    {
        return static::where('name', $name)->value('value');
    }

    public function getValueAttribute($value)
    {
        if (isset($this->attributes['type']) && $this->attributes['type'] == 'file') {

            if (! empty($value)) {
                /* Note : Because this is default logo so storage url will not work */
                if (Str::contains($value, 'assets')) {
                    return asset($value);
                }

                return url(Storage::url($value));
            }

            return '';
        }

        return $value;
    }

    public function getTranslatedValueAttribute()
    {
        return $this->getTranslatedValue('translated_value', $this->value);
    }
}
