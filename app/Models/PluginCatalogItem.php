<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginCatalogItem extends Model
{
    protected $fillable = [
        'slug', 'scope', 'name', 'description', 'marketplace_url', 'latest_version',
    ];
}
