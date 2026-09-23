<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model {
    use HasFactory, ManageTranslations;

    protected $fillable = [
        "id",
        "name",
        "state_id",
        "state_code",
        "country_id",
        "country_code",
        "latitude",
        "longitude",
        "created_at",
        "updated_at",
        "flag",
        "wikiDataId",
        "status",
    ];

    protected $appends = ['translated_name'];
     protected $with = ['translations'];
    public function state() {
        return $this->belongsTo(State::class);
    }

    public function country() {
        return $this->belongsTo(Country::class);
    }
    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function scopeSearch($query, $search) {
    $search = "%" . $search . "%";

    $query = $query->where(function ($q) use ($search) {
        $q->orWhere('cities.id', 'LIKE', $search)
            ->orWhere('cities.name', 'LIKE', $search)
            ->orWhere('cities.state_id', 'LIKE', $search)
            ->orWhere('cities.state_code', 'LIKE', $search)
            ->orWhere('cities.country_id', 'LIKE', $search)
            ->orWhere('cities.country_code', 'LIKE', $search)
            ->orWhereHas('state', function ($q) use ($search) {
                $q->where('states.name', 'LIKE', $search);
            })
            ->orWhereHas('country', function ($q) use ($search) {
                $q->where('countries.name', 'LIKE', $search);
            });
    });
    return $query;
}

   public function scopeSort($query, $column, $order) {
    if ($column == "country_name") {
        $query = $query->leftJoin('countries', 'countries.id', '=', 'cities.country_id')
                       ->orderBy('countries.name', $order);
    } elseif ($column == "state_name") {
        $query = $query->leftJoin('states', 'states.id', '=', 'cities.state_id')
                       ->orderBy('states.name', $order);
    } else {
        $query = $query->orderBy("cities.$column", $order);
    }
    return $query->select('cities.*');
}
    public function scopeFilter($query, $filterObject) {
        if (!empty($filterObject)) {
            foreach ($filterObject as $column => $value) {
                if($column == "state_name") {
                    $query->whereHas('state', function ($query) use ($value) {
                        $query->where('state_id', $value);
                    });
                }
                elseif($column == "country_name") {
                    $query->whereHas('country', function ($query) use ($value) {
                        $query->where('country_id', $value);
                    });
                }
                else {
                    $query->where((string)$column, (string)$value);
                }
            }
        }
        return $query;

    }
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
    public function getTranslatedNameAttribute()
    {
        return $this->getTranslatedValue('name', $this->name);
    }
}
