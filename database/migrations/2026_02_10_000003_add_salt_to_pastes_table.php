<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pastes', function (Blueprint $table) {
            $table->string('salt', 64)->nullable()->after('init_vector');
        });
    }

    public function down(): void
    {
        Schema::table('pastes', function (Blueprint $table) {
            $table->dropColumn('salt');
        });
    }
};
