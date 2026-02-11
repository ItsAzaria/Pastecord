<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32);
            $table->longText('content');
            $table->boolean('encrypted')->default(false);
            $table->boolean('password_protected')->default(false);
            $table->string('init_vector', 64)->nullable()->unique();
            $table->string('language')->default('plaintext');
            $table->string('discord_id', 20)->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('burn_after_read')->default(false);
            $table->unsignedInteger('read_count')->default(0);
            $table->string('content_hash', 64);
            $table->timestamps();

            $table->unique('key');
            $table->index('content_hash');
            $table->index('discord_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pastes');
    }
};
