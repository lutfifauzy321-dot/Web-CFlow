<?php
class ReportController {
    // Export CSV (bisa dibuka di Excel). PDF bisa ditambah nanti pakai Dompdf lewat Composer.
    static function export() {
        Auth::require();
        try { $filters = Transaction::normalizeFilters($_GET); }
        catch (InvalidArgumentException $e) {
            Helper::flash($e->getMessage());
            Helper::redirect('/transactions');
        }
        $rows = Transaction::list($_SESSION['uid'], $filters, 5000);
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=cflow-' . date('Ymd') . '.csv');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['Tanggal', 'Jenis', 'Kategori', 'Judul', 'Deskripsi', 'Metode', 'Tag', 'Nominal', 'Bukti']);
        foreach ($rows as $r) fputcsv($o, [$r['trx_date'], $r['type'] == 'income' ? 'Pemasukan' : 'Pengeluaran', $r['cat'], $r['title'], $r['note'], $r['method'], $r['tags'], $r['amount'], $r['receipt'] ? Helper::url('/uploads/receipts/' . $r['receipt']) : '']);
        exit;
    }
}
