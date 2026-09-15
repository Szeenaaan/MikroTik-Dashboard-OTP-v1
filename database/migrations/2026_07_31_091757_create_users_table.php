<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name', 100);

            $table->string('email', 150)->unique();

            $table->string('password');

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_login_at')->nullable();

            $table->unsignedTinyInteger('failed_login_attempts')->default(0);

            $table->boolean('locked')->default(false);

            $table->timestamp('locked_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};