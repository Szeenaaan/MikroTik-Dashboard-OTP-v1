<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wifi_users', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address')->unique();
            $table->string('phone_number');
            $table->string('old_phone_number')->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_logout_at')->nullable();

            $table->unsignedBigInteger('download_bytes')->default(0);
            $table->unsignedBigInteger('upload_bytes')->default(0);

            $table->boolean('is_active')->default(true);   // controls actual network access
            $table->string('status')->default('ACTIVE');   // hardcoded, dashboard filtering only

            $table->timestamp('blocked_at')->nullable();
            $table->string('blocked_reason')->nullable();

            $table->unsignedInteger('session_count')->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index('is_active');
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_users');
    }
};