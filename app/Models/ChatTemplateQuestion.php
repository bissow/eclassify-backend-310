<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatTemplateQuestion extends Model {
    use HasFactory, ManageTranslations;

    protected $fillable = [
        'chat_template_id',
        'role',
        'question',
        'sequence',
    ];

    protected $appends = ['translated_question'];

    public function translations() {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function chat_template() {
        return $this->belongsTo(ChatTemplate::class);
    }

    public function getTranslatedQuestionAttribute() {
        return $this->getTranslatedValue('question', $this->question);
    }
}
