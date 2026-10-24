<?php
class TransactionController {
    static function index() {
        $u = Auth::user();
        $f = ['q' => $_GET['q'] ?? '', 'type' => $_GET['type'] ?? '', 'cat' => $_GET['cat'] ?? '',
            'from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? ''];
        try { $f = Transaction::normalizeFilters($f); }
        catch (InvalidArgumentException $e) {
            Helper::flash($e->getMessage());
            $f['from'] = $f['to'] = '';
        }
        $items = Transaction::list($u['id'], $f, 200); $categories = Category::all();
        require ROOT . '/views/transactions/index.php';
    }
    static function store() {
        Auth::require(); Helper::verify(); $u = Auth::user(); $file = null;
        try {
            $type = $_POST['type'] === 'income' ? 'income' : 'expense';
            $amount = (int)preg_replace('/\D/', '', $_POST['amount'] ?? '');
            $cat = (int)($_POST['category_id'] ?? 0);
            $title = Helper::clean($_POST['title'] ?? '');
            $description = Helper::clean($_POST['description'] ?? '');
            if ($title === '' || mb_strlen($title) > 150) throw new Exception('Judul wajib diisi (maksimal 150 karakter).');
            if (mb_strlen($description) > 500) throw new Exception('Deskripsi maksimal 500 karakter.');
            $ok = db()->prepare('SELECT COUNT(*) FROM categories WHERE id=? AND type=?'); $ok->execute([$cat, $type]);
            if ($amount < 1 || !$ok->fetchColumn()) throw new Exception('Nominal dan kategori wajib diisi dengan benar.');
            $methods = ['BCA', 'GoPay', 'OVO', 'QRIS', 'ShopeePay', 'Cash'];
            $method = in_array($_POST['method'] ?? '', $methods) ? $_POST['method'] : 'Cash';
            $ts = strtotime($_POST['trx_date'] ?? '') ?: time();
            $file = Uploader::receipt($_FILES['receipt'] ?? null);
            $tags = implode(' ', array_map(fn($t) => '#' . ltrim($t, '#'), array_filter(preg_split('/[\s,]+/', Helper::clean($_POST['tags'] ?? '')))));
            $id = (int)($_POST['trx_id'] ?? 0);
            $data = ['category_id' => $cat, 'type' => $type, 'amount' => $amount, 'method' => $method,
                'title' => $title, 'description' => $description, 'tags' => $tags, 'receipt' => $file, 'trx_date' => date('Y-m-d H:i:s', $ts)];
            if ($id) { if (!Transaction::update($u['id'], $id, $data)) throw new Exception('Transaksi tidak ditemukan.'); Helper::flash('Perubahan tersimpan ✓'); }
            else { Transaction::create($u['id'], $data); Helper::flash('Transaksi tersimpan ✓'); }
        } catch (Exception $e) { Uploader::remove($file); Helper::flash($e->getMessage()); }
        Helper::redirect($_POST['back'] ?? '/');
    }
    static function delete() {
        Auth::require(); Helper::verify(); Transaction::delete($_SESSION['uid'], (int)$_POST['id']);
        Helper::flash('Transaksi dihapus.'); Helper::redirect($_POST['back'] ?? '/');
    }
    static function feature() {
        Auth::require(); Helper::verify();
        $uid = (int)$_SESSION['uid'];
        $id = (int)($_POST['id'] ?? 0);
        $featured = ($_POST['featured'] ?? '') === '1';
        $back = ($_POST['back'] ?? '/') === '/transactions' ? '/transactions' : '/';
        if ($id < 1) {
            Helper::flash('Transaksi tidak valid.');
            Helper::redirect($back);
        }
        $current = Transaction::featuredState($uid, $id);
        if ($current === null) {
            Helper::flash('Transaksi tidak ditemukan.');
            Helper::redirect($back);
        }
        if ($featured && !$current && Transaction::featuredCount($uid) >= 3) {
            Helper::flash('Maksimal 3 transaksi pilihan. Batalkan pilihan transaksi lain dulu.');
            Helper::redirect($back);
        }
        if ($current !== $featured && !Transaction::setFeatured($uid, $id, $featured)) {
            Helper::flash('Gagal memperbarui pilihan transaksi.');
            Helper::redirect($back);
        }
        Helper::flash($featured ? 'Transaksi ditambahkan ke Catatan Pilihan.' : 'Transaksi dihapus dari Catatan Pilihan.');
        Helper::redirect($back);
    }
    static function wishStore() {
        Auth::require(); Helper::verify(); $n = Helper::clean($_POST['name'] ?? ''); $p = (int)preg_replace('/\D/', '', $_POST['price'] ?? '');
        if ($n && $p) db()->prepare('INSERT INTO wishlist (user_id,name,price) VALUES (?,?,?)')->execute([$_SESSION['uid'], $n, $p]);
        Helper::redirect('/#wishlist');
    }
    static function wishDelete() {
        Auth::require(); Helper::verify(); db()->prepare('DELETE FROM wishlist WHERE id=? AND user_id=?')->execute([(int)$_POST['id'], $_SESSION['uid']]);
        Helper::redirect('/#wishlist');
    }
    static function limit() {
        Auth::require(); Helper::verify(); User::setLimit($_SESSION['uid'], (int)preg_replace('/\D/', '', $_POST['limit'] ?? '')); Helper::flash('Batas pengeluaran harian diperbarui.'); Helper::redirect('/');
    }
    static function goal() {
        Auth::require(); Helper::verify();
        $goal = filter_var($_POST['goal'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($goal === false) {
            Helper::flash('Masukkan target tabungan berupa angka rupiah yang lebih besar dari 0.');
            Helper::redirect('/');
        }
        User::setGoal((int)$_SESSION['uid'], $goal);
        Helper::flash('Target tabungan berhasil diperbarui.');
        Helper::redirect('/');
    }
    static function budget() {
        Auth::require(); Helper::verify();
        $uid = (int)$_SESSION['uid'];
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        $rawAmount = $_POST['amount'] ?? '';
        $amount = filter_var($rawAmount, FILTER_VALIDATE_INT);
        $month = $_POST['budget_month'] ?? '';
        $validMonth = is_string($month) && $month === date('Y-m');
        if (!$categoryId || $amount === false || $amount < 0 || !$validMonth) {
            Helper::flash('Kategori, bulan, atau nominal anggaran tidak valid.');
            Helper::redirect('/');
        }
        $check = db()->prepare("SELECT COUNT(*) FROM categories WHERE id=? AND type='expense'");
        $check->execute([$categoryId]);
        if (!(int)$check->fetchColumn()) {
            Helper::flash('Kategori pengeluaran tidak ditemukan.');
            Helper::redirect('/');
        }
        CategoryBudget::save($uid, $categoryId, $month, $amount);
        Helper::flash($amount === 0 ? 'Anggaran kategori dihapus.' : 'Anggaran kategori disimpan.');
        Helper::redirect('/');
    }
}
