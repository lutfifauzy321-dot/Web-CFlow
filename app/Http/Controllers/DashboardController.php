<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $u = auth()->user();
        $uid = (int) $u->id;
        $ym = date('Y-m');
        $m = Transaction::month($uid, $ym);
        $balance = Transaction::balance($uid);
        $cashTrends = Transaction::cashTrends($uid);
        $cats = Transaction::byCategory($uid, $ym);
        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $availableYears = Transaction::availableYears($uid);

        $selected = request('month');
        if (is_string($selected) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selected)) {
            [$summaryYear, $summaryMonthNumber] = array_map('intval', explode('-', $selected));
        } else {
            $summaryMonthNumber = filter_var($selected ?? date('n'), FILTER_VALIDATE_INT);
            $summaryYear = filter_var(request('year', date('Y')), FILTER_VALIDATE_INT);
        }
        $validSelection = $summaryMonthNumber !== false && $summaryMonthNumber >= 1 && $summaryMonthNumber <= 12
            && $summaryYear !== false && in_array($summaryYear, $availableYears, true);
        if (!$validSelection) {
            $summaryMonth = $ym;
            $summaryYear = (int) date('Y');
            $summaryMonthNumber = (int) date('n');
            session()->flash('legacy_flash', 'Pilihan bulan tidak valid. Ringkasan menampilkan bulan ini.');
        } else {
            $summaryMonth = sprintf('%04d-%02d', $summaryYear, $summaryMonthNumber);
        }

        $selectedMonth = new \DateTimeImmutable($summaryMonth . '-01');
        $monthlySummary = Transaction::monthSummary($uid, $summaryMonth);
        $previousMonth = $selectedMonth->modify('-1 month')->format('Y-m');
        $currentMonthLabel = $monthNames[(int) $selectedMonth->format('n')] . ' ' . $selectedMonth->format('Y');
        $previousDate = $selectedMonth->modify('-1 month');
        $previousMonthLabel = $monthNames[(int) $previousDate->format('n')] . ' ' . $previousDate->format('Y');

        $categoryBudgets = CategoryBudget::overview($uid, $ym);
        $bucket = Transaction::byBucket($uid, $ym);
        $methods = Transaction::byMethod($uid);
        $recent = Transaction::listForUser($uid, [], 8);
        $featured = Transaction::featured($uid, 3);
        $categories = Category::options();
        $wish = DB::table('wishlist')->where('user_id', $uid)->orderByDesc('id')->limit(5)->get()->map(fn ($row) => (array) $row)->all();

        $goal = max(1, (int) $u->savings_goal);
        $goalPct = min(100, round(max(0, $balance) / $goal * 100, 1));
        $day = (int) date('j');
        $left = (int) date('t') - $day + 1;
        $avg = $m['expense'] / $day;
        $manual = (int) $u->daily_limit;
        $limit = $manual > 0 ? $manual : (int) round(max(0, $m['income'] - $m['expense']) / $left);
        $today = Transaction::today($uid);
        $limPct = $limit > 0 ? min(100, round($today / $limit * 100)) : 0;
        $rate = $m['income'] > 0 ? ($m['income'] - $m['expense']) / $m['income'] : 0;
        $top = $cats[0]['name'] ?? '-';
        $inc = $m['income'];
        $plan = [
            'needs' => ['Kebutuhan (50%)', 'Kebutuhan rutin', $inc * .5, $bucket['needs'] ?? 0, '#60a5fa', 'Makan, tempat tinggal, tagihan, transportasi'],
            'wants' => ['Keinginan (30%)', 'Gaya hidup', $inc * .3, $bucket['wants'] ?? 0, '#f472b6', 'Hiburan, nongkrong, dan langganan'],
            'savings' => ['Tabungan (20%)', 'Simpan untuk nanti', $inc * .2, $bucket['savings'] ?? 0, '#10b981', 'Dana darurat dan tujuan keuangan'],
        ];

        return view('dashboard.index', compact(
            'u', 'uid', 'ym', 'm', 'balance', 'cashTrends', 'cats', 'monthNames', 'availableYears',
            'summaryYear', 'summaryMonthNumber', 'summaryMonth', 'previousMonth', 'currentMonthLabel',
            'previousMonthLabel', 'monthlySummary', 'categoryBudgets', 'bucket', 'methods', 'recent',
            'featured', 'categories', 'wish', 'goal', 'goalPct', 'day', 'left', 'avg', 'manual',
            'limit', 'today', 'limPct', 'rate', 'top', 'inc', 'plan'
        ));
    }
}
