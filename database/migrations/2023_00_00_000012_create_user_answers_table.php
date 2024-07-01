<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_answers', function (Blueprint $table) {
            $table->integer('user_answer_id')->autoIncrement();
            $table->integer('user_pkg_quest_id');
            $table->integer('choice_id');
            $table->text('user_essay_answer')->nullable();
            $table->foreignId('history_id')->nullable()->onDelete('cascade');
            $table->tinyInteger('choice_val')->nullable();
            $table->timestamps();

            // foreign
            // $table->foreign('essay_answer_id')->references('essay_answer_id')->on('essay_answer')->cascadeOnDelete();
            $table->foreign('user_pkg_quest_id')->references('user_pkg_quest_id')->on('user_pkg_quests')->cascadeOnDelete();
            $table->foreign('choice_id')->references('choice_id')->on('question_choices')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_answers');
    }
};
