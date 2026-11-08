<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->integer('onboarding_completed')->default(0);
            $table->timestamps();
        });

        // Opaque bearer-token sessions (renamed from the original `sessions`
        // table to avoid clashing with Laravel's own web-session concept).
        Schema::create('auth_tokens', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id');
            $table->string('token_hash')->index();
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_tokens');
        Schema::dropIfExists('users');
    }
};
