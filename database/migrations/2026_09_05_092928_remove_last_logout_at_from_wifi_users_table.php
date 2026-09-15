<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wifi_users', function (Blueprint $table) {
            $table->dropColumn('last_logout_at');
        });
    }

    public function down(): void
    {
        Schema::table('wifi_users', function (Blueprint $table) {
            $table->timestamp('last_logout_at')->nullable();
        });
    }
};