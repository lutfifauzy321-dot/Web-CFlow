<div id="modal" role="dialog" aria-modal="true" aria-label="Catat transaksi">
  <form id="trxForm" method="post" action="<?= Helper::url('/transactions/store') ?>" enctype="multipart/form-data" class="card w-full max-w-md space-y-3 !bg-[#0b121d]">
    <?= Helper::field() ?><input type="hidden" name="trx_id" value="">
    <input type="hidden" name="back" value="<?= Helper::e(($_GET['r'] ?? '') !== '' ? '/' . trim($_GET['r'], '/') : '/') ?>">
    <div class="flex justify-between items-center"><div><h2 id="modalTitle" class="font-extrabold text-lg">Catat transaksi</h2><p class="text-xs text-slate-500 mt-1">Isi jumlah dan detail transaksi.</p></div><button type="button" data-close-modal class="text-slate-400 text-xl" aria-label="Tutup">✕</button></div>
    <div class="seg flex gap-1 p-1 rounded-xl bg-black/30">
      <label class="exp"><input type="radio" name="type" value="expense" checked><span>Pengeluaran</span></label>
      <label><input type="radio" name="type" value="income"><span>Pemasukan</span></label>
    </div>
    <label for="amount" class="block text-xs text-slate-400">Jumlah (rupiah) <span class="text-rose-400">*</span>
      <input id="amount" name="amount" class="in !text-xl font-bold num mt-1" inputmode="numeric" placeholder="Contoh: 25000" required autocomplete="off">
    </label>
    <label for="trxTitle" class="block text-xs text-slate-400">Judul transaksi <span class="text-rose-400">*</span>
      <input id="trxTitle" name="title" class="in mt-1" maxlength="150" placeholder="Contoh: Makan siang" required>
    </label>
    <label for="trxNote" class="block text-xs text-slate-400"><span id="noteLabelText">Deskripsi / alasan pengeluaran (opsional)</span>
      <textarea id="trxNote" name="description" class="in mt-1 resize-y" rows="3" maxlength="500" placeholder="Contoh: Beli makan siang karena lembur atau kebutuhan darurat"></textarea>
    </label>
    <div class="grid sm:grid-cols-2 gap-2">
      <label class="block text-xs text-slate-400">Kategori
        <select id="category" name="category_id" class="in mt-1" required><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>"><?= $c['icon'] . ' ' . Helper::e($c['name']) ?></option><?php endforeach ?></select>
      </label>
      <label class="block text-xs text-slate-400">Rekening / dompet
        <select name="method" class="in mt-1"><?php foreach (['BCA', 'GoPay', 'OVO', 'QRIS', 'ShopeePay', 'Cash'] as $m): ?><option><?= $m ?></option><?php endforeach ?></select>
      </label>
    </div>
    <div class="grid sm:grid-cols-2 gap-2">
      <label class="block text-xs text-slate-400">Tanggal dan waktu
        <input type="datetime-local" name="trx_date" class="in mt-1" value="<?= date('Y-m-d\TH:i') ?>">
      </label>
      <label class="block text-xs text-slate-400">Tag (opsional)
        <input name="tags" class="in mt-1" placeholder="Contoh: makan, rutin">
      </label>
    </div>
    <label class="block border border-dashed border-slate-600 rounded-xl p-3 text-center text-xs text-slate-400 cursor-pointer hover:border-emerald-500">
      📎 Upload bukti transfer / foto barang (otomatis dikecilkan)
      <input id="receipt" type="file" name="receipt" accept="image/jpeg,image/png,image/webp" class="hidden">
      <img id="preview" class="hidden mx-auto mt-3 max-h-40 rounded-lg" alt="Preview bukti">
    </label>
    <button id="saveBtn" class="btn w-full !py-3">Simpan transaksi</button>
  </form>
</div>
