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
        Schema::create('essay_questions', function (Blueprint $table) {
            $table->increments('essay_id');
            $table->integer('tryout_sub_id');
            $table->text('essay_desc');
            $table->text('essay_img')->nullable();
            $table->tinyInteger('question_type')->default(0);
            $table->float('question_weight', 8, 3)->default(0.99);
            $table->timestamps();

            // menambahkan foreign key ke sub tryout
            $table->foreign('tryout_sub_id')->references('tryout_sub_id')->on('tryout_subs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('essay');
    }
};
