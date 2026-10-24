<?php $tab = 'transaksi'; require ROOT . '/views/layouts/header.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-6 space-y-4">
  <section class="page-intro">
    <div>
      <p class="eyebrow">Semua catatan uangmu</p>
      <h1 class="page-title">Riwayat transaksi</h1>
      <p class="page-description">Cari, saring, atau unduh transaksi yang pernah kamu catat.</p>
    </div>
  </section>
  <section class="card">
    <div id="transaction-filters" class="flex flex-wrap gap-3 items-start justify-between mb-4">
      <div><h2 class="font-extrabold text-lg">Cari transaksi</h2><p class="text-xs text-slate-500 mt-1">Gunakan kata kunci atau pilih jenis dan kategori.</p></div>
      <form class="transaction-filters" method="get" action="<?= Helper::url('/transactions') ?>"><input type="hidden" name="r" value="transactions">
        <input aria-label="Cari transaksi" name="q" value="<?= Helper::e($f['q']) ?>" class="in" maxlength="150" placeholder="Cari transaksi...">
        <select aria-label="Saring berdasarkan jenis" name="type" class="in"><option value="">Semua jenis</option><option value="income" <?= $f['type'] == 'income' ? 'selected' : '' ?>>Uang masuk</option><option value="expense" <?= $f['type'] == 'expense' ? 'selected' : '' ?>>Uang keluar</option></select>
        <select aria-label="Saring berdasarkan kategori" name="cat" class="in"><option value="">Semua kategori</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $f['cat'] == $c['id'] ? 'selected' : '' ?>><?= Helper::e($c['name']) ?></option><?php endforeach ?></select>
        <label class="date-filter">Dari tanggal
          <input aria-label="Dari tanggal" type="date" name="from" value="<?= Helper::e($f['from']) ?>" class="in">
        </label>
        <label class="date-filter">Sampai tanggal
          <input aria-label="Sampai tanggal" type="date" name="to" value="<?= Helper::e($f['to']) ?>" class="in">
        </label>
        <button class="btn btn-ghost">Terapkan filter</button>
        <a class="btn btn-ghost" href="<?= Helper::url('/export?' . http_build_query($f)) ?>">Unduh CSV</a>
      </form>
    </div>
    <div class="transaction-list space-y-2">
      <?php foreach ($items as $t) trxRow($t, '/transactions'); ?>
      <?php if (!$items): ?><div class="empty-state"><strong>Transaksi tidak ditemukan</strong><p>Coba ubah kata kunci atau filter. Kamu juga bisa mencatat transaksi baru.</p><button class="btn mt-3" data-open-modal>＋ Catat transaksi</button></div><?php endif ?>
    </div>
  </section>
</main>
<?php require ROOT . '/views/layouts/footer.php'; ?>
