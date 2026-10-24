<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use RuntimeException;

class TransactionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filters = [
            'q' => $request->query('q', ''),
            'type' => $request->query('type', ''),
            'cat' => $request->query('cat', ''),
            'from' => $request->query('from', ''),
            'to' => $request->query('to', ''),
        ];
        try {
            $filters = Transaction::normalizeFilters($filters);
        } catch (\InvalidArgumentException $exception) {
            return redirect('/transactions')->with('legacy_flash', $exception->getMessage());
        }

        return view('transactions.index', [
            'u' => $request->user(),
            'f' => $filters,
            'items' => Transaction::listForUser((int) $request->user()->id, $filters, 200),
            'categories' => Category::options(),
            'tab' => 'transaksi',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();
        $input['amount'] = is_string($input['amount'] ?? null)
            ? (int) preg_replace('/\D/', '', $input['amount'])
            : 0;
        $input['title'] = trim(strip_tags((string) ($input['title'] ?? '')));
        $input['description'] = trim(strip_tags((string) ($input['description'] ?? '')));
        $validator = Validator::make($input, [
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'method' => ['nullable', 'in:BCA,GoPay,OVO,QRIS,ShopeePay,Cash'],
            'tags' => ['nullable', 'string', 'max:120'],
            'trx_date' => ['required', 'date'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'trx_id' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($validator->fails()) {
            return $this->back($request)->with('legacy_flash', $validator->errors()->first());
        }

        $userId = (int) $request->user()->id;
        $categoryType = DB::table('categories')->where('id', $input['category_id'])->value('type');
        if ($categoryType !== $input['type']) {
            return $this->back($request)->with('legacy_flash', 'Nominal dan kategori wajib diisi dengan benar.');
        }

        $receipt = null;
        $oldReceiptToRemove = null;
        try {
            if ($request->hasFile('receipt')) {
                $receipt = UploadService::storeImage($request->file('receipt'), 'receipts');
            }
            $data = [
                'category_id' => (int) $input['category_id'],
                'type' => $input['type'],
                'amount' => $input['amount'],
                'method' => in_array($input['method'] ?? '', ['BCA', 'GoPay', 'OVO', 'QRIS', 'ShopeePay', 'Cash'], true)
                    ? $input['method']
                    : 'Cash',
                'title' => $input['title'],
                'description' => $input['description'] ?? '',
                'tags' => $this->normalizeTags($input['tags'] ?? ''),
                'receipt' => $receipt,
                'trx_date' => date('Y-m-d H:i:s', strtotime($input['trx_date'])),
            ];

            $id = (int) ($input['trx_id'] ?? 0);
            if ($id > 0) {
                $oldReceipt = Transaction::receiptForUser($userId, $id);
                if (!Transaction::updateForUser($userId, $id, $data)) {
                    throw new RuntimeException('Transaksi tidak ditemukan.');
                }
                if ($receipt !== null) {
                    $oldReceiptToRemove = $oldReceipt;
                }
                $message = 'Perubahan tersimpan ✓';
            } else {
                Transaction::createForUser($userId, $data);
                $message = 'Transaksi tersimpan ✓';
            }
        } catch (\Throwable $exception) {
            UploadService::removeReceipt($receipt);
            if ($exception instanceof RuntimeException) {
                $message = $exception->getMessage();
            } else {
                throw $exception;
            }
        }

        if ($oldReceiptToRemove !== null) {
            UploadService::removeReceipt($oldReceiptToRemove);
        }

        return $this->back($request)->with('legacy_flash', $message);
    }

    public function delete(Request $request): RedirectResponse
    {
        $data = $request->validate(['id' => ['required', 'integer', 'min:1']]);
        $receipt = Transaction::deleteForUser((int) $request->user()->id, (int) $data['id']);
        UploadService::removeReceipt($receipt);

        return $this->back($request)->with('legacy_flash', 'Transaksi dihapus.');
    }

    public function feature(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
            'featured' => ['required', 'in:0,1'],
        ]);
        $userId = (int) $request->user()->id;
        $id = (int) $data['id'];
        $featured = $data['featured'] === '1';
        $back = $this->safeBack($request);

        $result = DB::transaction(function () use ($userId, $id, $featured): ?string {
            User::query()->whereKey($userId)->lockForUpdate()->first();
            $current = Transaction::featuredState($userId, $id);
            if ($current === null) {
                return 'Transaksi tidak ditemukan.';
            }
            if ($featured && !$current && Transaction::featuredCount($userId) >= 3) {
                return 'Maksimal 3 transaksi pilihan. Batalkan pilihan transaksi lain dulu.';
            }
            if ($current !== $featured && !Transaction::setFeatured($userId, $id, $featured)) {
                return 'Gagal memperbarui pilihan transaksi.';
            }

            return null;
        });
        if ($result !== null) {
            return redirect($back)->with('legacy_flash', $result);
        }

        return redirect($back)->with(
            'legacy_flash',
            $featured ? 'Transaksi ditambahkan ke Catatan Pilihan.' : 'Transaksi dihapus dari Catatan Pilihan.'
        );
    }

    public function wishStore(Request $request): RedirectResponse
    {
        $input = $request->all();
        $input['name'] = trim(strip_tags((string) ($input['name'] ?? '')));
        $input['price'] = is_string($input['price'] ?? null) ? (int) preg_replace('/\D/', '', $input['price']) : 0;
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) {
            return redirect('/#wishlist')->with('legacy_flash', $validator->errors()->first());
        }

        DB::table('wishlist')->insert([
            'user_id' => $request->user()->id,
            'name' => $input['name'],
            'price' => $input['price'],
        ]);

        return redirect('/#wishlist');
    }

    public function wishDelete(Request $request): RedirectResponse
    {
        $data = $request->validate(['id' => ['required', 'integer', 'min:1']]);
        DB::table('wishlist')->where('id', $data['id'])->where('user_id', $request->user()->id)->delete();

        return redirect('/#wishlist');
    }

    public function limit(Request $request): RedirectResponse
    {
        $value = is_string($request->input('limit')) ? (int) preg_replace('/\D/', '', $request->input('limit')) : -1;
        if ($value < 0) {
            return redirect('/')->with('legacy_flash', 'Batas pengeluaran tidak valid.');
        }
        User::query()->whereKey($request->user()->id)->update(['daily_limit' => $value]);

        return redirect('/')->with('legacy_flash', 'Batas pengeluaran harian diperbarui.');
    }

    public function goal(Request $request): RedirectResponse
    {
        $data = $request->validate(['goal' => ['required', 'integer', 'min:1']]);
        User::query()->whereKey($request->user()->id)->update(['savings_goal' => $data['goal']]);

        return redirect('/')->with('legacy_flash', 'Target tabungan berhasil diperbarui.');
    }

    public function budget(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'budget_month' => ['required', 'date_format:Y-m'],
        ]);
        if ($data['budget_month'] !== date('Y-m')) {
            return redirect('/')->with('legacy_flash', 'Kategori, bulan, atau nominal anggaran tidak valid.');
        }
        if (DB::table('categories')->where('id', $data['category_id'])->where('type', 'expense')->doesntExist()) {
            return redirect('/')->with('legacy_flash', 'Kategori pengeluaran tidak ditemukan.');
        }

        CategoryBudget::saveBudget(
            (int) $request->user()->id,
            (int) $data['category_id'],
            $data['budget_month'],
            (int) $data['amount']
        );

        return redirect('/')->with(
            'legacy_flash',
            $data['amount'] === 0 ? 'Anggaran kategori dihapus.' : 'Anggaran kategori disimpan.'
        );
    }

    private function normalizeTags(mixed $tags): string
    {
        $clean = trim(strip_tags(is_string($tags) ? $tags : ''));
        $parts = preg_split('/[\s,]+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_map(fn ($tag) => '#' . ltrim($tag, '#'), $parts ?: []));
    }

    private function back(Request $request): RedirectResponse
    {
        return redirect($this->safeBack($request));
    }

    private function safeBack(Request $request): string
    {
        $path = $request->input('back', '/');

        return is_string($path) && str_starts_with($path, '/') && !str_starts_with($path, '//')
            ? $path
            : '/';
    }
}
