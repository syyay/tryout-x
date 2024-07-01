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
        Schema::create('essay_answer', function (Blueprint $table) {
            $table->integer('essay_answer_id')->autoIncrement();
            $table->foreignId('essay_id');   
            $table->text('true_essay_answer');
            $table->tinyInteger('choice_val')->nullable();
            $table->text('explanation');
            $table->timestamps();

            // foreign key
            // $table->foreign('essay_id')->references('essay_id')->on('essay_questions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('essay_answer');
    }
};
