<?php
class CategoryBudget {
    static function overview($uid, string $month): array {
        $start = $month . '-01';
        $end = date('Y-m-d', strtotime($start . ' +1 month'));
        $s = db()->prepare("SELECT c.id, c.name, c.icon,
                COALESCE(b.amount,0) budget,
                COALESCE(spent.amount,0) spent
            FROM categories c
            LEFT JOIN category_budgets b
                ON b.category_id=c.id AND b.user_id=? AND b.budget_month=?
            LEFT JOIN (
                SELECT category_id, SUM(amount) amount
                FROM transactions
                WHERE user_id=? AND type='expense' AND trx_date>=? AND trx_date<?
                GROUP BY category_id
            ) spent ON spent.category_id=c.id
            WHERE c.type='expense'
            ORDER BY c.id");
        $s->execute([$uid, $month, $uid, $start, $end]);
        return $s->fetchAll();
    }

    static function save(int $uid, int $categoryId, string $month, int $amount): void {
        if ($amount === 0) {
            db()->prepare('DELETE FROM category_budgets WHERE user_id=? AND category_id=? AND budget_month=?')
                ->execute([$uid, $categoryId, $month]);
            return;
        }
        db()->prepare('INSERT INTO category_budgets (user_id,category_id,budget_month,amount) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE amount=VALUES(amount)')
            ->execute([$uid, $categoryId, $month, $amount]);
    }
}
