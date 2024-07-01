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
        Schema::create('major_passing_grade', function (Blueprint $table) {
            $table->id('major_id');
            $table->foreignId('university_id')->onDelete('cascade');
            $table->string('major_name');
            $table->string('education_levels');
            $table->float('max_score')->nullable();
            $table->float('min_score')->nullable();
            $table->float('average_score')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('major_passing_grade');
    }
};
