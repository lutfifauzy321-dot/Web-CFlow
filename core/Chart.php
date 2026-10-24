<?php
// Grafik SVG yang digambar di server: nggak butuh Chart.js / CDN / cache JS.
class Chart {
    const COLORS = ['#8b5cf6', '#3b82f6', '#10b981', '#f472b6', '#f59e0b', '#06b6d4'];
    static function short($v): string {
        if ($v >= 1000000) return rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',') . 'jt';
        return $v >= 1000 ? round($v / 1000) . 'rb' : (string)(int)$v;
    }
    static function cashFlow(array $trends): string {
        $W = 520; $H = 230; $l = 54; $r = 10; $t = 12; $b = 30;
        $o = '<svg id="expenseChart" viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%;height:100%" role="img" aria-label="Grafik pemasukan dan pengeluaran" font-family="inherit" font-size="10" fill="#94a3b8">';
        foreach ($trends as $period => $trend) {
            $labels = array_values($trend['labels']);
            $series = [
                ['key' => 'income', 'label' => 'Pemasukan', 'color' => '#10b981', 'values' => array_values($trend['income'])],
                ['key' => 'expense', 'label' => 'Pengeluaran', 'color' => '#f43f5e', 'values' => array_values($trend['expense'])],
            ];
            $count = count($labels);
            $pw = $W - $l - $r; $ph = $H - $t - $b; $base = $t + $ph;
            $cx = fn($i) => round($l + ($i + .5) * $pw / $count, 1);
            foreach ($series as $dataset) {
                $max = max(1, ...$dataset['values']);
                if ($max >= 100000) {
                    $step = ceil($max / 400000) * 100000;
                } else {
                    $p = 10 ** floor(log10($max)); $step = $p;
                    foreach ([1, 2, 2.5, 5, 10] as $k) if ($max <= 4 * $k * $p) { $step = $k * $p; break; }
                }
                $top = $step * 4;
                $y = fn($v) => round($base - $v / $top * $ph, 1);
                $visible = $period === 'monthly' && $dataset['key'] === 'expense';
                $total = array_sum($dataset['values']);
                $o .= '<g data-period="' . $period . '" data-series="' . $dataset['key'] . '" data-total="' . $total . '"' . ($visible ? '' : ' style="display:none"') . '>';
                for ($i = 0; $i <= 4; $i++) {
                    $yy = $y($step * $i);
                    $o .= '<line x1="' . $l . '" x2="' . ($W - $r) . '" y1="' . $yy . '" y2="' . $yy . '" stroke="rgba(148,163,184,.12)"/><text x="' . ($l - 8) . '" y="' . ($yy + 3) . '" text-anchor="end">' . self::shortThousands($step * $i) . '</text>';
                }
                $points = [];
                foreach ($dataset['values'] as $i => $value) {
                    $points[] = $cx($i) . ',' . $y($value);
                    if ($period !== 'daily' || (int)substr($labels[$i], 0, 2) % 4 === 0) {
                        $o .= '<text x="' . $cx($i) . '" y="' . ($H - 8) . '" text-anchor="middle">' . Helper::e($labels[$i]) . '</text>';
                    }
                }
                $area = 'M ' . $cx(0) . ' ' . $base . ' L ' . implode(' L ', $points) . ' L ' . $cx($count - 1) . ' ' . $base . ' Z';
                $o .= '<path d="' . $area . '" fill="' . $dataset['color'] . '" fill-opacity=".09"/>';
                $o .= '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="' . $dataset['color'] . '" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';
                foreach ($dataset['values'] as $i => $value) {
                    if ($value > 0) {
                        $o .= '<circle cx="' . $cx($i) . '" cy="' . $y($value) . '" r="4" fill="' . $dataset['color'] . '" stroke="#0f1622" stroke-width="2"><title>' . Helper::e($labels[$i]) . ' • ' . $dataset['label'] . ' ' . Helper::rp($value) . '</title></circle>';
                    }
                }
                $o .= '</g>';
            }
        }
        return $o . '</svg>';
    }
    private static function shortThousands($v): string {
        if ($v >= 1000000) return rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',') . 'jt';
        return $v >= 1000 ? number_format($v / 1000, 0, ',', '.') . 'rb' : (string)(int)$v;
    }
    private static function group(array $cats): array {
        $cats = array_values($cats); $main = array_slice($cats, 0, 5); $rest = array_sum(array_column(array_slice($cats, 5), 'a'));
        if ($rest > 0) $main[] = ['name' => 'Lainnya', 'a' => $rest];
        return $main;
    }
    static function donut(array $cats): string {
        $g = self::group($cats); $tot = max(1, array_sum(array_column($g, 'a'))); $C = 2 * M_PI * 42; $off = 0;
        $o = '<svg viewBox="0 0 120 120" style="width:100%;height:100%" role="img" aria-label="Sebaran pengeluaran per kategori"><circle cx="60" cy="60" r="42" fill="none" stroke="rgba(148,163,184,.12)" stroke-width="14"/>';
        foreach ($g as $i => $c) {
            $len = $c['a'] / $tot * $C;
            $o .= '<circle cx="60" cy="60" r="42" fill="none" stroke="' . self::COLORS[$i % 6] . '" stroke-width="14" stroke-dasharray="' . round($len, 2) . ' ' . round($C - $len, 2) . '" stroke-dashoffset="' . round(-$off, 2) . '" transform="rotate(-90 60 60)"><title>' . Helper::e($c['name']) . ' ' . Helper::rp($c['a']) . '</title></circle>';
            $off += $len;
        }
        return $o . '<text x="60" y="57" text-anchor="middle" font-size="7" fill="#94a3b8">Total</text><text x="60" y="68" text-anchor="middle" font-size="11" font-weight="800" fill="#e2e8f0">' . self::short($tot) . '</text></svg>';
    }
    static function legend(array $cats): string {
        $g = self::group($cats); $tot = max(1, array_sum(array_column($g, 'a'))); $o = '<ul class="mt-3 space-y-1 text-[11px]">';
        foreach ($g as $i => $c) $o .= '<li class="flex items-center gap-2"><i class="w-2 h-2 rounded-full" style="background:' . self::COLORS[$i % 6] . '"></i><span class="truncate flex-1 text-slate-300">' . Helper::e($c['name']) . '</span><b class="num">' . round($c['a'] / $tot * 100) . '%</b></li>';
        return $o . '</ul>';
    }
}
