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
        Schema::create('user_pkg_quests', function (Blueprint $table) {
            $table->integer('user_pkg_quest_id')->autoIncrement();
            $table->uuid('user_pkg_id');
            $table->integer('question_id');
            $table->foreignId('essay_id')->nullable();
            $table->boolean('flag_question')->default(false);
            $table->timestamps();

            // foreign key
            // $table->foreign('essay_id')->references('essay_id')->on('essay_questions')->cascadeOnDelete();
            $table->foreign('user_pkg_id')->references('user_pkg_id')->on('user_pkgs')->cascadeOnDelete();
            $table->foreign('question_id')->references('question_id')->on('tryout_questions')->cascadeOnDelete();
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pkg_quests');
    }
};
