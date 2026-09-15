<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexExists = DB::selectOne("
            SELECT COUNT(*) AS count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'otp_sessions'
              AND index_name = 'otp_sessions_created_at_index'
        ");

        if ((int) $indexExists->count === 0) {
            Schema::table('otp_sessions', function (Blueprint $table) {
                $table->index('created_at', 'otp_sessions_created_at_index');
            });
        }
    }

    public function down(): void
    {
        $indexExists = DB::selectOne("
            SELECT COUNT(*) AS count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'otp_sessions'
              AND index_name = 'otp_sessions_created_at_index'
        ");

        if ((int) $indexExists->count > 0) {
            Schema::table('otp_sessions', function (Blueprint $table) {
                $table->dropIndex('otp_sessions_created_at_index');
            });
        }
    }
};