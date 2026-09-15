<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->foreignId('wifi_user_id')
                ->after('id')
                ->constrained('wifi_users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->dropForeign(['wifi_user_id']);
            $table->dropColumn('wifi_user_id');
        });
    }
};