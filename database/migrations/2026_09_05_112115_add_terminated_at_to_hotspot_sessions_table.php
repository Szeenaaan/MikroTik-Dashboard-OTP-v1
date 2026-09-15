<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->dateTime('terminated_at')->nullable()->after('session_end');
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->dropColumn('terminated_at');
        });
    }
};