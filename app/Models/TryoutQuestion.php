<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TryoutQuestion extends Model
{
    use HasFactory;

    protected $table = "tryout_questions";
    protected $primaryKey = "question_id";
    protected $keyType = "integer";
    protected $fillable = [
        "tryout_sub_id",
        "question_desc",
        "question_img",
        "question_type",
        "question_weight"
    ];
    protected $hidden = [
        "tryout_sub_id",
        "question_weight",
        "true_count",
        "created_at",
        "updated_at",
    ];
    protected $appends = [
        "true_count",
    ];
    public $incrementing = true;

    // many to many
    public function user_pkgs(): BelongsToMany
    {
        return $this->belongsToMany(UserPKG::class, "user_pkg_quests", "question_id", "user_pkg_id")
            ->using(UserPKGQuest::class)
            ->withPivot([
                "user_pkg_quest_id",
                "user_pkg_id",
                "question_id",
                "flag_question"
            ])
            ->orderByPivot("user_pkg_quest_id", "asc")
            ->withTimestamps();
    }

    // one to many
    public function question_choices(): HasMany
    {
        return $this->hasMany(QuestionChoice::class, 'question_id', 'question_id');
    }
    public function user_pkg_questions(): HasMany
    {
        return $this->hasMany(UserPKGQuest::class, 'question_id', 'question_id');
    }

    // many to one
    public function tryout_subs(): BelongsTo
    {
        return $this->belongsTo(TryoutSub::class, 'tryout_sub_id', 'tryout_sub_id');
    }

    // appended attribute
    public function getTrueCountAttribute(): float
    {
        $count = $this->question_choices()->where('true_answer', 1)->count();
        return $count;
    }

    // method to calculate question weight
    public function weight_question(): void
    {
        // get all user question answer
        $user_q_ans = $this->user_pkg_questions()->get();
        if ($user_q_ans->first() == null) {
            return;
        }

        $count = $user_q_ans->count();
        $true_question_count = 0;

        // count true question
        foreach ($user_q_ans as $user_question_answer) {
            if ($user_question_answer->question_true == true) {
                $true_question_count++;
            }
        }

        // calculate question weight
        $result = 1 - (floatval($true_question_count) / floatval($count));

        // set question weight to 0.01 if result is 0, and 0.99 if result is 1
        if ($result == 0) {
            $result = 0.01;
        } else if ($result == 1) {
            $result = 0.99;
        }

        // update question weight if it's different
        if ($this->question_weight != $result) {
            $this->update(['question_weight' => $result]);
        }
        return;
    }

    // method to check essay answer
    public function checkEssayAnswer(int $user_pkg_id, string $user_answer): bool
    {
    $correct_answer = $this->evaluateEssayAnswer($user_answer);

    // Save the answer to the pivot table
    $this->user_pkgs()
         ->wherePivot('user_pkg_id', $user_pkg_id)
         ->updateExistingPivot($user_pkg_id, [
             'user_answer' => $user_answer,
             'question_true' => $correct_answer,
         ]);

    return $correct_answer;
    }
    
    // method to evaluate the essay answer
    private function evaluateEssayAnswer(string $user_answer): bool
    {
        // Logic to evaluate the answer
        // For example, if the correct answer is stored in the question_desc
        $correct_answer = $this->question_desc;

        // Check if the user's answer matches the correct answer
        return trim(strtolower($user_answer)) === trim(strtolower($correct_answer));
    }
}