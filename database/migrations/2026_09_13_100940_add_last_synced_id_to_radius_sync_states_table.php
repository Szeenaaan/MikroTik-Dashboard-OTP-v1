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
              AND table_name = 'hotspot_sessions'
              AND index_name = 'idx_hotspot_sessions_radius_lookup'
        ");

        if ((int) $indexExists->count === 0) {
            Schema::table('hotspot_sessions', function (Blueprint $table) {
                $table->index(
                    [
                        'mac',
                        'radius_username',
                        'nas_ip',
                        'status',
                        'is_active',
                        'session_start',
                    ],
                    'idx_hotspot_sessions_radius_lookup'
                );
            });
        }
    }

    public function down(): void
    {
        $indexExists = DB::selectOne("
            SELECT COUNT(*) AS count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'hotspot_sessions'
              AND index_name = 'idx_hotspot_sessions_radius_lookup'
        ");

        if ((int) $indexExists->count > 0) {
            Schema::table('hotspot_sessions', function (Blueprint $table) {
                $table->dropIndex('idx_hotspot_sessions_radius_lookup');
            });
        }
    }
};