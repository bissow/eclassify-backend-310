<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory, ManageTranslations;

    protected $fillable = [
        'id',
        'name',
        'iso3',
        'numeric_code',
        'iso2',
        'phonecode',
        'capital',
        'currency',
        'currency_name',
        'currency_symbol',
        'tld',
        'native',
        'region',
        'region_id',
        'subregion',
        'subregion_id',
        'nationality',
        'timezones',
        'latitude',
        'longitude',
        'emoji',
        'emojiU',
        'created_at',
        'updated_at',
        'flag',
        'wikiDataId',
        'status',
    ];

    protected $appends = ['translated_name'];

    protected $with = ['translations'];

    public function scopeSearch($query, $search)
    {
        $search = '%'.$search.'%';
        $query = $query->where(function ($q) use ($search) {
            $q->orWhere('id', 'LIKE', $search)
                ->orWhere('name', 'LIKE', $search)
                ->orWhere('numeric_code', 'LIKE', $search)
                ->orWhere('phonecode', 'LIKE', $search);
        });

        return $query;
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function getTranslatedNameAttribute()
    {
        return $this->getTranslatedValue('name', $this->name);
    }

    public function currency()
    {
        return $this->hasOne(Currency::class);
    }
}
