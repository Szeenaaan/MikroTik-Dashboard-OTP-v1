<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hotspot_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wifi_user_id')
                ->nullable()
                ->constrained('wifi_users')
                ->nullOnDelete();

            $table->foreignId('otp_session_id')
                ->constrained('otp_sessions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('radius_username', 100);

            $table->string('radius_password');

            $table->string('mac', 50);

            $table->string('ip', 45);

            $table->string('nas_ip', 45);

            $table->string('session_id')->unique();

            $table->timestamp('session_start');

            $table->timestamp('session_end')->nullable();

            $table->unsignedBigInteger('download_bytes')->default(0);

            $table->unsignedBigInteger('upload_bytes')->default(0);

            $table->unsignedInteger('session_time')->default(0);

            $table->string('status', 20)->nullable()->default('AVAILABLE');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('mac');
            $table->index('wifi_user_id');
            $table->index('status');
            $table->index('session_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_sessions');
    }
};