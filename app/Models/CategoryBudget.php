<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategoryBudget extends Model
{
    protected $table = 'category_budgets';

    public $timestamps = false;

    public static function overview(int $userId, string $month): array
    {
        $start = $month . '-01';
        $end = date('Y-m-d', strtotime($start . ' +1 month'));

        $rows = DB::select(
            "SELECT c.id, c.name, c.icon,
                    COALESCE(b.amount, 0) AS budget,
                    COALESCE(spent.amount, 0) AS spent
             FROM categories c
             LEFT JOIN category_budgets b
               ON b.category_id = c.id AND b.user_id = ? AND b.budget_month = ?
             LEFT JOIN (
                 SELECT category_id, SUM(amount) AS amount
                 FROM transactions
                 WHERE user_id = ? AND type = 'expense' AND trx_date >= ? AND trx_date < ?
                 GROUP BY category_id
             ) spent ON spent.category_id = c.id
             WHERE c.type = 'expense'
             ORDER BY c.id",
            [$userId, $month, $userId, $start, $end]
        );

        return array_map(fn ($row) => (array) $row, $rows);
    }

    public static function saveBudget(int $userId, int $categoryId, string $month, int $amount): void
    {
        if ($amount === 0) {
            DB::table('category_budgets')
                ->where('user_id', $userId)
                ->where('category_id', $categoryId)
                ->where('budget_month', $month)
                ->delete();

            return;
        }

        DB::table('category_budgets')->updateOrInsert(
            ['user_id' => $userId, 'category_id' => $categoryId, 'budget_month' => $month],
            ['amount' => $amount]
        );
    }
}
