<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (!Schema::hasColumn('users', 'password_hash')) {
                    $table->string('password_hash')->nullable();
                }
                if (!Schema::hasColumn('users', 'daily_limit')) {
                    $table->bigInteger('daily_limit')->default(0);
                }
                if (!Schema::hasColumn('users', 'savings_goal')) {
                    $table->bigInteger('savings_goal')->default(10000000);
                }
            });
        }

        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            if (!Schema::hasColumn('transactions', 'note')) {
                $table->string('note', 500)->nullable();
            }
            if (!Schema::hasColumn('transactions', 'featured')) {
                $table->boolean('featured')->default(false);
            }
            if (!Schema::hasColumn('transactions', 'title')) {
                $table->string('title', 150)->nullable();
            }
        });

        if (Schema::hasColumn('transactions', 'title')) {
            DB::table('transactions')->where(function ($query): void {
                $query->whereNull('title')->orWhere('title', '');
            })->orderBy('id')->chunkById(250, function ($rows): void {
                foreach ($rows as $row) {
                    $note = trim((string) ($row->note ?? ''));
                    $title = $note === '' || $note === 'Tanpa judul' ? 'Transaksi' : mb_substr($note, 0, 150);
                    DB::table('transactions')->where('id', $row->id)->update(['title' => $title]);
                }
            });
        }

        if (DB::getDriverName() === 'mysql') {
            $noteType = DB::selectOne(
                "SELECT CHARACTER_MAXIMUM_LENGTH AS max_length
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'transactions' AND column_name = 'note'"
            );
            if ($noteType && $noteType->max_length !== null && (int) $noteType->max_length < 500) {
                Schema::table('transactions', function (Blueprint $table): void {
                    $table->string('note', 500)->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // This migration adapts existing CFlow tables without removing their data.
    }
};
