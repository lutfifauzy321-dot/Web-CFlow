<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('google_id', 64)->nullable()->unique();
                $table->string('name', 100);
                $table->string('email', 150)->unique();
                $table->string('avatar')->nullable();
                $table->unsignedBigInteger('savings_goal')->default(10000000);
                $table->unsignedBigInteger('daily_limit')->default(0);
                $table->string('password_hash')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Existing user data is adopted in place and must survive rollback.
    }
};
