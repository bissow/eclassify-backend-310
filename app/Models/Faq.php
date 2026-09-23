<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory, ManageTranslations;

    protected $fillable=[
        'question',
        'answer'

    ];
    protected $appends = ['translated_question','translated_answer'];
    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function getTranslation($languageId)
    {
        return $this->translations->where('language_id', $languageId)->first();
    }

    public function getTranslatedQuestionAttribute()
    {
        return $this->getTranslatedValue('question', $this->question);
    }

    public function getTranslatedAnswerAttribute()
    {
        return $this->getTranslatedValue('answer', $this->answer);
    }
}
