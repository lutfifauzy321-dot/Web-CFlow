<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userIdUnsigned = $this->idIsUnsigned('users');
        $categoryIdUnsigned = $this->idIsUnsigned('categories');

        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 60);
                $table->enum('type', ['income', 'expense']);
                $table->enum('bucket', ['needs', 'wants', 'savings'])->nullable();
                $table->string('icon', 8);
            });
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) use ($userIdUnsigned, $categoryIdUnsigned): void {
                $table->increments('id');
                $userIdUnsigned ? $table->unsignedInteger('user_id') : $table->integer('user_id');
                $categoryIdUnsigned ? $table->unsignedInteger('category_id') : $table->integer('category_id');
                $table->enum('type', ['income', 'expense']);
                $table->unsignedBigInteger('amount');
                $table->string('title', 150)->default('Transaksi');
                $table->string('method', 20)->default('Cash');
                $table->string('note', 500)->nullable();
                $table->string('tags', 120)->nullable();
                $table->string('receipt', 120)->nullable();
                $table->boolean('featured')->default(false);
                $table->dateTime('trx_date');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['user_id', 'trx_date']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('category_id')->references('id')->on('categories');
            });
        }

        if (!Schema::hasTable('wishlist')) {
            Schema::create('wishlist', function (Blueprint $table) use ($userIdUnsigned): void {
                $table->increments('id');
                $userIdUnsigned ? $table->unsignedInteger('user_id') : $table->integer('user_id');
                $table->string('name', 100);
                $table->unsignedBigInteger('price');
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('category_budgets')) {
            Schema::create('category_budgets', function (Blueprint $table) use ($userIdUnsigned, $categoryIdUnsigned): void {
                $table->increments('id');
                $userIdUnsigned ? $table->unsignedInteger('user_id') : $table->integer('user_id');
                $categoryIdUnsigned ? $table->unsignedInteger('category_id') : $table->integer('category_id');
                $table->char('budget_month', 7);
                $table->unsignedBigInteger('amount');
                $table->unique(['user_id', 'category_id', 'budget_month'], 'user_category_month');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // This migration may adopt tables that already contain user data.
    }

    private function idIsUnsigned(string $table): bool
    {
        if (!Schema::hasTable($table) || DB::getDriverName() !== 'mysql') {
            return true;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE 'id'");

        return $column !== null && str_contains(strtolower($column->Type), 'unsigned');
    }
};
