<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        try {
            $filters = Transaction::normalizeFilters($request->query());
        } catch (\InvalidArgumentException $exception) {
            return redirect('/transactions')->with('legacy_flash', $exception->getMessage());
        }

        $rows = Transaction::listForUser((int) auth()->id(), $filters, 5000);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Jenis', 'Kategori', 'Judul', 'Deskripsi', 'Metode', 'Tag', 'Nominal', 'Bukti']);
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['trx_date'],
                    $row['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    $row['cat'],
                    $row['title'],
                    $row['note'],
                    $row['method'],
                    $row['tags'],
                    $row['amount'],
                    $row['receipt'] ? url('/uploads/receipts/' . $row['receipt']) : '',
                ]);
            }
            fclose($output);
        }, 'cflow-' . date('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
