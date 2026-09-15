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
        Schema::create('otp_sessions', function (Blueprint $table) {
            $table->id();

            $table->string('phone', 20);

            $table->string('otp');

            $table->string('mac', 50);

            $table->string('ip', 45);

            $table->string('router_name', 100);

            $table->string('router_ip', 45);

            $table->string('router_host')->nullable();

            $table->text('link_login');

            $table->text('link_orig')->nullable();

            $table->string('status', 20)->default('pending');

            $table->unsignedTinyInteger('attempts')->default(0);

            $table->unsignedTinyInteger('resend_count')->default(0);

            $table->timestamp('last_resend_at')->nullable();

            $table->timestamp('expires_at');

            $table->timestamp('verified_at')->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('phone');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_sessions');
    }
};