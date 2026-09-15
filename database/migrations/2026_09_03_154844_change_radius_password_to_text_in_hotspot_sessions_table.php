<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->text('radius_password')->change();
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->string('radius_password', 191)->change();
        });
    }
};