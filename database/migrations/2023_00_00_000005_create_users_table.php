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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('username', 25);
            $table->string('full_name', 40);
            $table->string('salt', 10);
            $table->string('password', 100);
            $table->string('email', 40)->unique();
            $table->date('birth_date');
            $table->string('phone_num', 15);
            // $table->string('prov', 20);
            // $table->string('city', 20);
            $table->string('school', 20);
            $table->string('major', 10);
            $table->year('grad_date');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->string('image', 225)->nullable();
            $table->string('role')->default('user');
            $table->string('university1')->nullable();
            $table->string('major1')->nullable();
            $table->string('university2')->nullable();
            $table->string('major2')->nullable();
            $table->timestamps();
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
