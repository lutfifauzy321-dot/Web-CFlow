<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('categories')->exists()) {
            return;
        }

        DB::table('categories')->insert([
            ['name' => 'Gaji & Income', 'type' => 'income', 'bucket' => null, 'icon' => '💼'],
            ['name' => 'Freelance', 'type' => 'income', 'bucket' => null, 'icon' => '💻'],
            ['name' => 'Bonus & Lainnya', 'type' => 'income', 'bucket' => null, 'icon' => '🎁'],
            ['name' => 'Makan & Minuman', 'type' => 'expense', 'bucket' => 'needs', 'icon' => '🍜'],
            ['name' => 'Transport', 'type' => 'expense', 'bucket' => 'needs', 'icon' => '🛵'],
            ['name' => 'Tagihan & Kos', 'type' => 'expense', 'bucket' => 'needs', 'icon' => '🏠'],
            ['name' => 'Langganan & Subscriptions', 'type' => 'expense', 'bucket' => 'wants', 'icon' => '📺'],
            ['name' => 'Lifestyle & Hangout', 'type' => 'expense', 'bucket' => 'wants', 'icon' => '☕'],
            ['name' => 'Belanja', 'type' => 'expense', 'bucket' => 'wants', 'icon' => '🛍️'],
            ['name' => 'Investasi & Crypto', 'type' => 'expense', 'bucket' => 'savings', 'icon' => '📈'],
            ['name' => 'Tabungan', 'type' => 'expense', 'bucket' => 'savings', 'icon' => '🐖'],
        ]);
    }
}
