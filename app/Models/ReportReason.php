<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportReason extends Model {
    use HasFactory, ManageTranslations;

    protected $fillable = [
        'reason'
    ];
     protected $appends = ['translated_reason'];
    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        $query = $query->where(function ($q) use ($search) {
            $q->orWhere('reason', 'LIKE', $search)
                ->orWhere('created_at', 'LIKE', $search)
                ->orWhere('updated_at', 'LIKE', $search);
        });
        return $query;
    }
    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function getTranslatedReasonAttribute()
    {
        return $this->getTranslatedValue('reason', $this->reason);
    }
}
