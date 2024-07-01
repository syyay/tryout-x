<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EssayAnswer extends Model
{
    use HasFactory;

    protected $table = "essay_answer";
    protected $primaryKey = "essay_answer_id";
    protected $keyType = "integer";
    protected $fillable = [
        "essay_id",
        "true_essay_answer",
        "choice_val",
        "explanation"
    ];
    protected $hidden = [
        "essay_id",
        "true_essay_answer",
        "choice_val",
        "explanation",
        "answer_weight",
        "created_at",
        "updated_at",
    ];
    protected $appends = [
        "answer_weight"
    ];
    public $incrementing = true;

    public function essayQuestion(): BelongsTo
    {
        return $this->belongsTo(Essay::class, 'essay_id', 'essay_id');
    }

    public function isRelatedEssay()
    {
        return $this->essayQuestion->isEssay();
    }

  


    // public function getChoiceWeightAttribute(): float
    // {
    //     $parent = $this->questions()->first();
    //     $weight = $parent->question_weight;
    //     $count = $parent->true_count;
    //     if ($this->true_answer == 0 || $count == null) {
    //         return 0;
    //     }
    //     return floatval($weight) / floatval($count);
    // }
}
