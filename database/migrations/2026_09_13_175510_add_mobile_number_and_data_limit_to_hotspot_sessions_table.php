<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->string('mobile_number', 20)->after('radius_password');
            $table->unsignedBigInteger('data_limit')->after('session_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_number',
                'data_limit',
            ]);
        });
    }
};
