<?php

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Transaction extends Model
{
    protected $table = 'transactions';

    public $timestamps = false;

    public static function listForUser(int $userId, array $filters = [], int $limit = 100): array
    {
        $where = 't.user_id = ?';
        $params = [$userId];

        if (!empty($filters['q'])) {
            $where .= ' AND (t.title LIKE ? OR t.note LIKE ? OR t.tags LIKE ?)';
            $query = '%' . $filters['q'] . '%';
            array_push($params, $query, $query, $query);
        }
        if (!empty($filters['type']) && in_array($filters['type'], ['income', 'expense'], true)) {
            $where .= ' AND t.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['cat'])) {
            $where .= ' AND t.category_id = ?';
            $params[] = (int) $filters['cat'];
        }
        if (!empty($filters['from'])) {
            $where .= ' AND t.trx_date >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where .= ' AND t.trx_date <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        $rows = DB::select(
            "SELECT t.*, c.name AS cat, c.icon
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE {$where}
             ORDER BY t.trx_date DESC, t.id DESC
             LIMIT " . max(1, min(5000, $limit)),
            $params
        );

        return array_map(fn ($row) => (array) $row, $rows);
    }

    public static function featured(int $userId, int $limit = 3): array
    {
        $rows = DB::select(
            'SELECT t.*, c.name AS cat, c.icon
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = ? AND t.featured = 1
             ORDER BY t.id DESC
             LIMIT ' . max(1, min(3, $limit)),
            [$userId]
        );

        return array_map(fn ($row) => (array) $row, $rows);
    }

    public static function featuredCount(int $userId): int
    {
        return DB::table('transactions')->where('user_id', $userId)->where('featured', 1)->count();
    }

    public static function featuredState(int $userId, int $id): ?bool
    {
        $state = DB::table('transactions')->where('id', $id)->where('user_id', $userId)->value('featured');

        return $state === null ? null : (bool) $state;
    }

    public static function setFeatured(int $userId, int $id, bool $featured): bool
    {
        return DB::table('transactions')->where('id', $id)->where('user_id', $userId)
            ->update(['featured' => $featured ? 1 : 0]) > 0;
    }

    public static function createForUser(int $userId, array $data): int
    {
        return (int) DB::table('transactions')->insertGetId([
            'user_id' => $userId,
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'title' => $data['title'],
            'note' => $data['description'],
            'tags' => $data['tags'],
            'receipt' => $data['receipt'],
            'trx_date' => $data['trx_date'],
        ]);
    }

    public static function updateForUser(int $userId, int $id, array $data): bool
    {
        $query = DB::table('transactions')->where('id', $id)->where('user_id', $userId);
        if (!$query->exists()) {
            return false;
        }

        $fields = [
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'title' => $data['title'],
            'note' => $data['description'],
            'tags' => $data['tags'],
            'trx_date' => $data['trx_date'],
        ];
        if ($data['receipt'] !== null) {
            $fields['receipt'] = $data['receipt'];
        }

        $query->update($fields);

        return true;
    }

    public static function receiptForUser(int $userId, int $id): ?string
    {
        return DB::table('transactions')->where('id', $id)->where('user_id', $userId)->value('receipt');
    }

    public static function deleteForUser(int $userId, int $id): ?string
    {
        $receipt = self::receiptForUser($userId, $id);
        if ($receipt !== null || DB::table('transactions')->where('id', $id)->where('user_id', $userId)->exists()) {
            DB::table('transactions')->where('id', $id)->where('user_id', $userId)->delete();
        }

        return $receipt;
    }

    public static function today(int $userId): int
    {
        return (int) DB::table('transactions')->where('user_id', $userId)->where('type', 'expense')
            ->whereDate('trx_date', date('Y-m-d'))->sum('amount');
    }

    public static function balance(int $userId): int
    {
        return (int) DB::selectOne(
            "SELECT COALESCE(SUM(IF(type = 'income', amount, -amount)), 0) AS balance
             FROM transactions WHERE user_id = ?",
            [$userId]
        )->balance;
    }

    public static function month(int $userId, string $month): array
    {
        $start = $month . '-01';
        $end = (new DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
        $rows = DB::select(
            'SELECT type, SUM(amount) AS amount FROM transactions
             WHERE user_id = ? AND trx_date >= ? AND trx_date < ? GROUP BY type',
            [$userId, $start, $end]
        );
        $result = ['income' => 0, 'expense' => 0];
        foreach ($rows as $row) {
            $result[$row->type] = (int) $row->amount;
        }

        return $result;
    }

    public static function monthSummary(int $userId, string $month): array
    {
        $start = new DateTimeImmutable($month . '-01');
        $totals = [];
        foreach ([$start, $start->modify('-1 month')] as $period) {
            $next = $period->modify('+1 month');
            $rows = DB::select(
                'SELECT type, SUM(amount) AS amount FROM transactions
                 WHERE user_id = ? AND trx_date >= ? AND trx_date < ? GROUP BY type',
                [$userId, $period->format('Y-m-d'), $next->format('Y-m-d')]
            );
            $result = ['income' => 0, 'expense' => 0];
            foreach ($rows as $row) {
                $result[$row->type] = (int) $row->amount;
            }
            $result['saved'] = $result['income'] - $result['expense'];
            $totals[$period->format('Y-m')] = $result;
        }

        return ['current' => $totals[$start->format('Y-m')], 'previous' => $totals[$start->modify('-1 month')->format('Y-m')]];
    }

    public static function availableYears(int $userId): array
    {
        $firstDate = DB::table('transactions')->where('user_id', $userId)->min('trx_date');
        $firstYear = $firstDate ? (int) substr((string) $firstDate, 0, 4) : 0;
        $currentYear = (int) date('Y');
        $firstYear = $firstYear > 0 ? min($firstYear, $currentYear - 10) : $currentYear - 10;

        return range($currentYear, $firstYear);
    }

    public static function normalizeFilters(array $filters): array
    {
        foreach (['q', 'type', 'cat', 'from', 'to'] as $key) {
            if (isset($filters[$key]) && !is_string($filters[$key])) {
                throw new InvalidArgumentException('Filter transaksi tidak valid.');
            }
            $filters[$key] = $filters[$key] ?? '';
        }
        $filters['q'] = mb_substr($filters['q'], 0, 150);
        if (!in_array($filters['type'], ['', 'income', 'expense'], true)) {
            $filters['type'] = '';
        }
        if ($filters['cat'] !== '' && !ctype_digit($filters['cat'])) {
            $filters['cat'] = '';
        }
        foreach (['from', 'to'] as $key) {
            if ($filters[$key] === '') {
                continue;
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $filters[$key]);
            if (!$date || $date->format('Y-m-d') !== $filters[$key]) {
                throw new InvalidArgumentException('Format tanggal tidak valid.');
            }
        }
        if ($filters['from'] !== '' && $filters['to'] !== '' && $filters['from'] > $filters['to']) {
            throw new InvalidArgumentException('Tanggal mulai tidak boleh setelah tanggal akhir.');
        }

        return $filters;
    }

    public static function byCategory(int $userId, string $month): array
    {
        $start = $month . '-01';
        $end = (new DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
        $rows = DB::select(
            "SELECT c.name, SUM(t.amount) AS a
             FROM transactions t JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = ? AND t.type = 'expense' AND t.trx_date >= ? AND t.trx_date < ?
             GROUP BY c.id ORDER BY a DESC",
            [$userId, $start, $end]
        );

        return array_map(fn ($row) => (array) $row, $rows);
    }

    public static function byBucket(int $userId, string $month): array
    {
        $start = $month . '-01';
        $end = (new DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
        $rows = DB::select(
            "SELECT c.bucket, SUM(t.amount) AS a
             FROM transactions t JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = ? AND t.type = 'expense' AND t.trx_date >= ? AND t.trx_date < ?
             GROUP BY c.bucket",
            [$userId, $start, $end]
        );

        return array_column(array_map(fn ($row) => (array) $row, $rows), 'a', 'bucket');
    }

    public static function byMethod(int $userId): array
    {
        $rows = DB::select(
            "SELECT method, SUM(IF(type = 'income', amount, -amount)) AS a
             FROM transactions WHERE user_id = ? GROUP BY method",
            [$userId]
        );

        return array_column(array_map(fn ($row) => (array) $row, $rows), 'a', 'method');
    }

    public static function cashTrends(int $userId): array
    {
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $monthly = ['labels' => [], 'income' => [], 'expense' => []];
        $monthStart = strtotime(date('Y-m-01') . ' -5 months');
        $monthEnd = strtotime(date('Y-m-01') . ' +1 month');
        for ($ts = $monthStart; $ts < $monthEnd; $ts = strtotime('+1 month', $ts)) {
            $key = date('Y-m', $ts);
            $monthly['labels'][$key] = $months[(int) date('n', $ts)];
            $monthly['income'][$key] = $monthly['expense'][$key] = 0;
        }
        $rows = DB::select(
            'SELECT trx_date, type, amount FROM transactions
             WHERE user_id = ? AND trx_date >= ? AND trx_date < ?',
            [$userId, date('Y-m-01', $monthStart), date('Y-m-01', $monthEnd)]
        );
        foreach ($rows as $row) {
            $period = substr($row->trx_date, 0, 7);
            if (isset($monthly[$row->type][$period])) {
                $monthly[$row->type][$period] += (int) $row->amount;
            }
        }

        $weekly = ['labels' => [], 'income' => [], 'expense' => []];
        $weekStart = strtotime('-6 days');
        $weekEnd = strtotime('+1 day');
        for ($ts = $weekStart; $ts < $weekEnd; $ts = strtotime('+1 day', $ts)) {
            $key = date('Y-m-d', $ts);
            $weekly['labels'][$key] = $days[(int) date('w', $ts)] . ' ' . date('j', $ts);
            $weekly['income'][$key] = $weekly['expense'][$key] = 0;
        }
        $rows = DB::select(
            'SELECT trx_date, type, amount
             FROM transactions WHERE user_id = ? AND trx_date >= ? AND trx_date < ?',
            [$userId, date('Y-m-d', $weekStart), date('Y-m-d', $weekEnd)]
        );
        foreach ($rows as $row) {
            $period = substr($row->trx_date, 0, 10);
            if (isset($weekly[$row->type][$period])) {
                $weekly[$row->type][$period] += (int) $row->amount;
            }
        }

        $daily = ['labels' => [], 'income' => [], 'expense' => []];
        for ($hour = 0; $hour < 24; $hour++) {
            $key = sprintf('%02d', $hour);
            $daily['labels'][$key] = $key . ':00';
            $daily['income'][$key] = $daily['expense'][$key] = 0;
        }
        $rows = DB::select(
            'SELECT trx_date, type, amount
             FROM transactions WHERE user_id = ? AND trx_date >= ? AND trx_date < ?',
            [$userId, date('Y-m-d'), date('Y-m-d', strtotime('+1 day'))]
        );
        foreach ($rows as $row) {
            $key = substr($row->trx_date, 11, 2);
            if (isset($daily[$row->type][$key])) {
                $daily[$row->type][$key] += (int) $row->amount;
            }
        }

        $trends = [
            'monthly' => &$monthly,
            'weekly' => &$weekly,
            'daily' => &$daily,
        ];
        foreach ($trends as &$trend) {
            foreach (['labels', 'income', 'expense'] as $key) {
                $trend[$key] = array_values($trend[$key]);
            }
        }
        unset($trend, $trends);

        return ['monthly' => $monthly, 'weekly' => $weekly, 'daily' => $daily];
    }
}
