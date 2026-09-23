<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static upsert(array $values, array $uniqueBy, array $update = null)
 */
class ChatTemplateCategory extends Model {
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'chat_template_id',
    ];

    public function chat_template() {
        return $this->hasOne(ChatTemplate::class, 'id', 'chat_template_id');
    }

    public function category() {
        return $this->hasOne(Category::class);
    }
}
