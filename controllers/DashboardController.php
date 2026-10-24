<?php
class DashboardController {
    static function index() {
        $u = Auth::user(); $uid = $u['id']; $ym = date('Y-m');
        $m = Transaction::month($uid, $ym); $balance = Transaction::balance($uid);
        $cashTrends = Transaction::cashTrends($uid); $cats = Transaction::byCategory($uid, $ym);
        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $availableYears = Transaction::availableYears($uid);
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)($_GET['month'] ?? ''))) {
            [$summaryYear, $summaryMonthNumber] = array_map('intval', explode('-', $_GET['month']));
        } else {
            $summaryMonthNumber = filter_var($_GET['month'] ?? date('n'), FILTER_VALIDATE_INT);
            $summaryYear = filter_var($_GET['year'] ?? date('Y'), FILTER_VALIDATE_INT);
        }
        $validSelection = $summaryMonthNumber !== false && $summaryMonthNumber >= 1 && $summaryMonthNumber <= 12
            && $summaryYear !== false && in_array($summaryYear, $availableYears, true);
        if (!$validSelection) {
            $summaryMonth = $ym;
            $summaryYear = (int)date('Y');
            $summaryMonthNumber = (int)date('n');
            Helper::flash('Pilihan bulan tidak valid. Ringkasan menampilkan bulan ini.');
        } else $summaryMonth = sprintf('%04d-%02d', $summaryYear, $summaryMonthNumber);
        $selectedMonth = new DateTimeImmutable($summaryMonth . '-01');
        $monthlySummary = Transaction::monthSummary($uid, $summaryMonth);
        $previousMonth = $selectedMonth->modify('-1 month')->format('Y-m');
        $currentMonthLabel = $monthNames[(int)$selectedMonth->format('n')] . ' ' . $selectedMonth->format('Y');
        $previousMonthLabel = $monthNames[(int)$selectedMonth->modify('-1 month')->format('n')] . ' ' . $selectedMonth->modify('-1 month')->format('Y');
        $categoryBudgets = CategoryBudget::overview($uid, $ym);
        $bucket = Transaction::byBucket($uid, $ym); $methods = Transaction::byMethod($uid);
        $recent = Transaction::list($uid, [], 8); $featured = Transaction::featured($uid, 3); $categories = Category::all();
        $wish = db()->prepare('SELECT * FROM wishlist WHERE user_id=? ORDER BY id DESC LIMIT 5'); $wish->execute([$uid]); $wish = $wish->fetchAll();

        $goal = max(1, (int)$u['savings_goal']); $goalPct = min(100, round(max(0, $balance) / $goal * 100, 1));
        $day = (int)date('j'); $left = (int)date('t') - $day + 1;
        $avg = $m['expense'] / $day; $manual = (int)$u['daily_limit'];
        $limit = $manual > 0 ? $manual : (int)round(max(0, $m['income'] - $m['expense']) / $left);
        $today = Transaction::today($uid); $limPct = $limit > 0 ? min(100, round($today / $limit * 100)) : 0;
        $rate = $m['income'] > 0 ? ($m['income'] - $m['expense']) / $m['income'] : 0;
        $score = (int)max(0, min(100, round(50 + $rate * 100)));
        $tip = $m['income'] == 0 ? 'Catat pemasukan pertamamu biar aku bisa kasih insight.'
            : ($rate >= .2 ? 'Gokil, keuangan lo aman banget! Sisa ' . round($rate * 100) . '% dari pemasukan bulan ini. Pertahankan gaya hidup hemat ini.'
            : ($rate >= 0 ? 'Masih aman, tapi sisa cuma ' . round($rate * 100) . '%. Coba kurangi jajan yang nggak penting.' : 'Pengeluaran lebih besar dari pemasukan. Rem dulu, ya.'));
        $top = $cats[0]['name'] ?? '-';
        $inc = $m['income'];
        $plan = [
            'needs'   => ['Kebutuhan (50%)', 'Kebutuhan rutin', $inc * .5, $bucket['needs'] ?? 0, '#60a5fa', 'Makan, tempat tinggal, tagihan, transportasi'],
            'wants'   => ['Keinginan (30%)', 'Gaya hidup', $inc * .3, $bucket['wants'] ?? 0, '#f472b6', 'Hiburan, nongkrong, dan langganan'],
            'savings' => ['Tabungan (20%)', 'Simpan untuk nanti', $inc * .2, $bucket['savings'] ?? 0, '#10b981', 'Dana darurat dan tujuan keuangan'],
        ];
        require ROOT . '/views/dashboard/index.php';
    }
}
