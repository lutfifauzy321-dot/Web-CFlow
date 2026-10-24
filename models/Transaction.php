<?php
class Transaction {
    static function list($uid, $f = [], $limit = 100): array {
        $w = 't.user_id=?'; $p = [$uid];
        if (!empty($f['q']))    { $w .= ' AND (t.title LIKE ? OR t.note LIKE ? OR t.tags LIKE ?)'; $p[] = $p[] = $p[] = '%' . $f['q'] . '%'; }
        if (!empty($f['type']) && in_array($f['type'], ['income', 'expense'])) { $w .= ' AND t.type=?'; $p[] = $f['type']; }
        if (!empty($f['cat']))  { $w .= ' AND t.category_id=?'; $p[] = (int)$f['cat']; }
        if (!empty($f['from'])) { $w .= ' AND t.trx_date>=?'; $p[] = $f['from'] . ' 00:00:00'; }
        if (!empty($f['to']))   { $w .= ' AND t.trx_date<=?'; $p[] = $f['to'] . ' 23:59:59'; }
        $s = db()->prepare("SELECT t.*, c.name cat, c.icon FROM transactions t JOIN categories c ON c.id=t.category_id
            WHERE $w ORDER BY t.trx_date DESC, t.id DESC LIMIT " . (int)$limit);
        $s->execute($p); return $s->fetchAll();
    }
    static function featured($uid, $limit = 3): array {
        $s = db()->prepare("SELECT t.*, c.name cat, c.icon FROM transactions t JOIN categories c ON c.id=t.category_id
            WHERE t.user_id=? AND t.featured=1 ORDER BY t.id DESC LIMIT " . (int)$limit);
        $s->execute([$uid]); return $s->fetchAll();
    }
    static function featuredCount($uid): int {
        $s = db()->prepare('SELECT COUNT(*) FROM transactions WHERE user_id=? AND featured=1');
        $s->execute([$uid]); return (int)$s->fetchColumn();
    }
    static function featuredState($uid, $id): ?bool {
        $s = db()->prepare('SELECT featured FROM transactions WHERE id=? AND user_id=?');
        $s->execute([$id, $uid]); $state = $s->fetchColumn();
        return $state === false ? null : (bool)$state;
    }
    static function setFeatured($uid, $id, bool $featured): bool {
        $s = db()->prepare('UPDATE transactions SET featured=? WHERE id=? AND user_id=?');
        $s->execute([$featured ? 1 : 0, $id, $uid]); return $s->rowCount() > 0;
    }
    static function create($uid, $d) {
        db()->prepare('INSERT INTO transactions (user_id,category_id,type,amount,method,title,note,tags,receipt,trx_date) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$uid, $d['category_id'], $d['type'], $d['amount'], $d['method'], $d['title'], $d['description'], $d['tags'], $d['receipt'], $d['trx_date']]);
    }
    static function update($uid, $id, $d): bool {
        $s = db()->prepare('SELECT receipt FROM transactions WHERE id=? AND user_id=?'); $s->execute([$id, $uid]);
        if (!($o = $s->fetch())) return false;
        if ($d['receipt']) Uploader::remove($o['receipt']);
        db()->prepare('UPDATE transactions SET category_id=?,type=?,amount=?,method=?,title=?,note=?,tags=?,receipt=?,trx_date=? WHERE id=? AND user_id=?')
            ->execute([$d['category_id'], $d['type'], $d['amount'], $d['method'], $d['title'], $d['description'], $d['tags'], $d['receipt'] ?: $o['receipt'], $d['trx_date'], $id, $uid]);
        return true;
    }
    static function today($uid): int {
        $s = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='expense' AND DATE(trx_date)=?");
        $s->execute([$uid, date('Y-m-d')]); return (int)$s->fetchColumn();
    }
    static function delete($uid, $id) {
        $s = db()->prepare('SELECT receipt FROM transactions WHERE id=? AND user_id=?'); $s->execute([$id, $uid]);
        if ($r = $s->fetch()) { Uploader::remove($r['receipt']); db()->prepare('DELETE FROM transactions WHERE id=? AND user_id=?')->execute([$id, $uid]); }
    }
    static function balance($uid): int {
        $s = db()->prepare("SELECT COALESCE(SUM(IF(type='income',amount,-amount)),0) FROM transactions WHERE user_id=?"); $s->execute([$uid]); return (int)$s->fetchColumn();
    }
    static function month($uid, $ym): array {
        $s = db()->prepare("SELECT type, SUM(amount) a FROM transactions WHERE user_id=? AND DATE_FORMAT(trx_date,'%Y-%m')=? GROUP BY type");
        $s->execute([$uid, $ym]); $r = ['income' => 0, 'expense' => 0];
        foreach ($s as $x) $r[$x['type']] = (int)$x['a']; return $r;
    }
    static function monthSummary($uid, string $month): array {
        $start = new DateTimeImmutable($month . '-01');
        $previous = $start->modify('-1 month');
        $totals = [];
        foreach ([$start, $previous] as $period) {
            $next = $period->modify('+1 month');
            $s = db()->prepare("SELECT type, SUM(amount) amount FROM transactions
                WHERE user_id=? AND trx_date>=? AND trx_date<? GROUP BY type");
            $s->execute([$uid, $period->format('Y-m-d'), $next->format('Y-m-d')]);
            $result = ['income' => 0, 'expense' => 0];
            foreach ($s->fetchAll() as $row) $result[$row['type']] = (int)$row['amount'];
            $result['saved'] = $result['income'] - $result['expense'];
            $totals[$period->format('Y-m')] = $result;
        }
        return ['current' => $totals[$start->format('Y-m')], 'previous' => $totals[$previous->format('Y-m')]];
    }
    static function availableYears($uid): array {
        $s = db()->prepare('SELECT MIN(YEAR(trx_date)) FROM transactions WHERE user_id=?');
        $s->execute([$uid]);
        $firstYear = (int)$s->fetchColumn();
        $currentYear = (int)date('Y');
        $firstYear = $firstYear > 0 ? min($firstYear, $currentYear - 10) : $currentYear - 10;
        return range($currentYear, $firstYear);
    }
    static function normalizeFilters(array $filters): array {
        foreach (['q', 'type', 'cat', 'from', 'to'] as $key) {
            if (isset($filters[$key]) && !is_string($filters[$key])) {
                throw new InvalidArgumentException('Filter transaksi tidak valid.');
            }
            $filters[$key] = $filters[$key] ?? '';
        }
        $filters['q'] = mb_substr($filters['q'], 0, 150);
        if (!in_array($filters['type'], ['', 'income', 'expense'], true)) $filters['type'] = '';
        if ($filters['cat'] !== '' && !ctype_digit($filters['cat'])) $filters['cat'] = '';
        foreach (['from', 'to'] as $key) {
            if ($filters[$key] === '') continue;
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
    static function byCategory($uid, $ym): array {
        $s = db()->prepare("SELECT c.name, SUM(t.amount) a FROM transactions t JOIN categories c ON c.id=t.category_id
            WHERE t.user_id=? AND t.type='expense' AND DATE_FORMAT(t.trx_date,'%Y-%m')=? GROUP BY c.id ORDER BY a DESC");
        $s->execute([$uid, $ym]); return $s->fetchAll();
    }
    static function byBucket($uid, $ym): array {
        $s = db()->prepare("SELECT c.bucket, SUM(t.amount) a FROM transactions t JOIN categories c ON c.id=t.category_id
            WHERE t.user_id=? AND t.type='expense' AND DATE_FORMAT(t.trx_date,'%Y-%m')=? GROUP BY c.bucket");
        $s->execute([$uid, $ym]); return array_column($s->fetchAll(), 'a', 'bucket');
    }
    static function byMethod($uid): array {
        $s = db()->prepare("SELECT method, SUM(IF(type='income',amount,-amount)) a FROM transactions WHERE user_id=? GROUP BY method");
        $s->execute([$uid]); return array_column($s->fetchAll(), 'a', 'method');
    }
    static function weekly($uid, $ym): array {
        $s = db()->prepare("SELECT type, LEAST(4,CEIL(DAY(trx_date)/7)) w, SUM(amount) a FROM transactions
            WHERE user_id=? AND DATE_FORMAT(trx_date,'%Y-%m')=? GROUP BY type, w");
        $s->execute([$uid, $ym]); $r = ['income' => [0, 0, 0, 0], 'expense' => [0, 0, 0, 0]];
        foreach ($s as $x) $r[$x['type']][(int)$x['w'] - 1] = (int)$x['a']; return $r;
    }
    static function cashTrends($uid): array {
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $monthly = ['labels' => [], 'income' => [], 'expense' => []];
        $monthStart = strtotime(date('Y-m-01') . ' -5 months');
        $monthEnd = strtotime(date('Y-m-01') . ' +1 month');
        for ($ts = $monthStart; $ts < $monthEnd; $ts = strtotime('+1 month', $ts)) {
            $key = date('Y-m', $ts);
            $monthly['labels'][$key] = $months[(int)date('n', $ts)];
            $monthly['income'][$key] = $monthly['expense'][$key] = 0;
        }
        $s = db()->prepare("SELECT DATE_FORMAT(trx_date,'%Y-%m') period, type, SUM(amount) amount FROM transactions
            WHERE user_id=? AND trx_date>=? AND trx_date<? GROUP BY period, type");
        $s->execute([$uid, date('Y-m-01', $monthStart), date('Y-m-01', $monthEnd)]);
        foreach ($s->fetchAll() as $row) if (isset($monthly[$row['type']][$row['period']])) $monthly[$row['type']][$row['period']] = (int)$row['amount'];

        $weekly = ['labels' => [], 'income' => [], 'expense' => []];
        $weekStart = strtotime('-6 days');
        $weekEnd = strtotime('+1 day');
        for ($ts = $weekStart; $ts < $weekEnd; $ts = strtotime('+1 day', $ts)) {
            $key = date('Y-m-d', $ts);
            $weekly['labels'][$key] = $days[(int)date('w', $ts)] . ' ' . date('j', $ts);
            $weekly['income'][$key] = $weekly['expense'][$key] = 0;
        }
        $s = db()->prepare("SELECT DATE(trx_date) period, type, SUM(amount) amount FROM transactions
            WHERE user_id=? AND trx_date>=? AND trx_date<? GROUP BY period, type");
        $s->execute([$uid, date('Y-m-d', $weekStart), date('Y-m-d', $weekEnd)]);
        foreach ($s->fetchAll() as $row) if (isset($weekly[$row['type']][$row['period']])) $weekly[$row['type']][$row['period']] = (int)$row['amount'];

        $daily = ['labels' => [], 'income' => [], 'expense' => []];
        for ($hour = 0; $hour < 24; $hour++) {
            $key = sprintf('%02d', $hour);
            $daily['labels'][$key] = $key . ':00';
            $daily['income'][$key] = $daily['expense'][$key] = 0;
        }
        $s = db()->prepare("SELECT HOUR(trx_date) period, type, SUM(amount) amount FROM transactions
            WHERE user_id=? AND DATE(trx_date)=? GROUP BY period, type");
        $s->execute([$uid, date('Y-m-d')]);
        foreach ($s->fetchAll() as $row) $daily[$row['type']][sprintf('%02d', $row['period'])] = (int)$row['amount'];

        foreach ([$monthly, $weekly, $daily] as &$trend) {
            $trend = [
                'labels' => array_values($trend['labels']),
                'income' => array_values($trend['income']),
                'expense' => array_values($trend['expense']),
            ];
        }
        unset($trend);
        return ['monthly' => $monthly, 'weekly' => $weekly, 'daily' => $daily];
    }
}
