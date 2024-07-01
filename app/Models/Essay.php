<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Essay extends Model
{
    use HasFactory;

    protected $table = "essay_questions";
    protected $primaryKey = "essay_id";
    protected $fillable = [
        'tryout_sub_id',
        'essay_description',
        'essay_img',
        'question_type',
    ];

    // Relasi ke tabel sub tryout
    public function subTryout(): BelongsTo
    {
        return $this->belongsTo(TryoutSub::class, 'tryout_sub_id', 'tryout_sub_id');
    }

    // relasi ke tabel user pkg questions
    public function user_pkg_essay_questions(): HasMany
    {
        return $this->hasMany(UserPKGQuest::class, 'essay_id');
    }

    // relasi ke tabel essay answer
    public function essayAnswer(): HasOne
    {
        return $this->hasMany(EssayAnswer::class, 'essay_id');
    }

    public function isEssay()
    {
        return $this->question_type === 2; // Asumsi 2 adalah untuk esai
    }
}
