<?php $tab = 'dashboard';
require ROOT . '/views/layouts/header.php'; ?>
<main id="top" class="max-w-6xl mx-auto px-4 py-6 space-y-4">

  <section class="page-intro">
    <div>
      <p class="eyebrow">Ringkasan keuangan</p>
      <h1 class="page-title">Halo, <?= Helper::e(explode(' ', trim($u['name']))[0]) ?> 👋</h1>
      <p class="page-description">Pantau uang masuk dan keluar, lalu catat transaksi kapan saja.</p>
    </div>
  </section>

  <section class="card grid md:grid-cols-[1.4fr_1fr_1fr] gap-4">
    <div>
      <div class="text-xs text-slate-400">Total saldo</div>
      <div class="num text-4xl md:text-5xl font-extrabold mt-1"><?= Helper::rp($balance) ?></div>
      <p class="text-xs text-slate-500 mt-2">Jumlah dari seluruh pemasukan dikurangi pengeluaran.</p>
    </div>
    <div class="card !bg-emerald-500/5"><div class="text-xs text-slate-400">Uang masuk bulan ini</div><div class="num text-2xl font-extrabold text-emerald-400 mt-2"><?= Helper::rp($m['income']) ?></div></div>
    <div class="card !bg-rose-500/5"><div class="text-xs text-slate-400">Uang keluar bulan ini</div><div class="num text-2xl font-extrabold text-rose-400 mt-2"><?= Helper::rp($m['expense']) ?></div></div>
    <div class="md:col-span-3">
      <div class="flex flex-wrap justify-between items-center gap-2 text-xs mb-2">
        <span>Progres target tabungan: <b class="text-slate-200"><?= Helper::rp($u['savings_goal']) ?></b></span>
        <div class="flex items-center gap-3"><b class="text-emerald-400"><?= $goalPct ?>%</b>
          <details class="setting-control">
            <summary>Ubah target</summary>
            <form id="goalForm" method="post" action="<?= Helper::url('/goal') ?>"><?= Helper::field() ?>
              <label for="goalAmount">Target tabungan (rupiah)</label>
              <input id="goalAmount" name="goal" type="number" min="1" step="1" inputmode="numeric" value="<?= (int)$u['savings_goal'] ?>" class="in" required>
              <p>Masukkan angka rupiah tanpa titik, contoh: 10000000.</p>
              <button class="btn">Simpan target</button>
            </form>
          </details>
        </div>
      </div>
      <div class="bar"><i style="width:<?= $goalPct ?>%;background:var(--mint)"></i></div>
    </div>
  </section>

  <section class="card" id="monthly-summary">
    <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
      <div>
        <h2 class="font-extrabold text-lg">Ringkasan bulanan</h2>
        <p class="text-xs text-slate-500 mt-1">Pilih bulan untuk melihat perbandingan dengan bulan sebelumnya.</p>
      </div>
      <form method="get" action="<?= Helper::url('/#monthly-summary') ?>" class="month-picker">
        <label class="month-picker-field" for="summaryMonth">Bulan
          <select id="summaryMonth" name="month" class="in">
            <?php foreach (array_slice($monthNames, 1, 12, true) as $number => $name): ?>
              <option value="<?= $number ?>" <?= $number === (int)$summaryMonthNumber ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach ?>
          </select>
        </label>
        <label class="month-picker-field" for="summaryYear">Tahun
          <select id="summaryYear" name="year" class="in">
            <?php foreach ($availableYears as $year): ?>
              <option value="<?= $year ?>" <?= $year === (int)$summaryYear ? 'selected' : '' ?>><?= $year ?></option>
            <?php endforeach ?>
          </select>
        </label>
        <button class="btn month-picker-submit">Tampilkan</button>
      </form>
    </div>
    <div class="grid sm:grid-cols-3 gap-3">
      <?php foreach ([
          ['Uang masuk', 'income', 'text-emerald-400'],
          ['Uang keluar', 'expense', 'text-rose-400'],
          ['Sisa uang', 'saved', 'text-sky-300'],
      ] as [$label, $key, $color]):
          $currentValue = $monthlySummary['current'][$key];
          $previousValue = $monthlySummary['previous'][$key];
          $change = $currentValue - $previousValue;
          $changeColor = $key === 'expense'
              ? ($change < 0 ? 'text-emerald-400' : ($change > 0 ? 'text-rose-300' : 'text-slate-400'))
              : ($change > 0 ? 'text-emerald-400' : ($change < 0 ? 'text-rose-300' : 'text-slate-400'));
      ?>
        <div class="summary-item">
          <span class="text-xs text-slate-400"><?= $label ?></span>
          <strong class="num <?= $color ?>"><?= Helper::rp($currentValue) ?></strong>
          <span class="summary-comparison">Bulan lalu (<?= $previousMonthLabel ?>): <?= Helper::rp($previousValue) ?></span>
          <span class="summary-change <?= $changeColor ?>">
            <?= $change > 0 ? 'Naik ' : ($change < 0 ? 'Turun ' : 'Tidak berubah ') ?><?= $change !== 0 ? Helper::rp(abs($change)) : '' ?> dari bulan lalu
          </span>
        </div>
      <?php endforeach ?>
    </div>
  </section>

  <section class="card" id="category-budgets">
    <div class="mb-4">
      <h2 class="font-extrabold text-lg">Anggaran per kategori</h2>
      <p class="text-xs text-slate-500 mt-1">Atur batas pengeluaran bulan ini. Isi Rp 0 jika belum ingin membatasi kategori.</p>
    </div>
    <div class="budget-grid">
      <?php foreach ($categoryBudgets as $budget):
          $budgetAmount = (int)$budget['budget'];
          $spentAmount = (int)$budget['spent'];
          $budgetPct = $budgetAmount > 0 ? min(100, round($spentAmount / $budgetAmount * 100)) : 0;
          $overBudget = $budgetAmount > 0 && $spentAmount > $budgetAmount;
          $atLimit = $budgetAmount > 0 && $spentAmount === $budgetAmount;
      ?>
        <article class="budget-item">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <h3 class="text-sm font-bold break-words"><?= $budget['icon'] ?> <?= Helper::e($budget['name']) ?></h3>
              <p class="text-xs text-slate-400 mt-1">Terpakai <?= Helper::rp($spentAmount) ?></p>
            </div>
            <?php if ($budgetAmount > 0): ?>
              <span class="chip shrink-0 <?= $overBudget ? '!text-rose-300 !bg-rose-500/15' : ($atLimit ? '!text-amber-300 !bg-amber-500/15' : '!text-emerald-300 !bg-emerald-500/15') ?>">
                <?= $overBudget ? 'Melebihi batas' : ($atLimit ? 'Batas tercapai' : $budgetPct . '%') ?>
              </span>
            <?php else: ?><span class="chip shrink-0">Belum diatur</span><?php endif ?>
          </div>
          <div class="bar mt-3"><i style="width:<?= $budgetPct ?>%;background:<?= $overBudget ? '#f43f5e' : ($atLimit ? '#f59e0b' : 'var(--mint)') ?>"></i></div>
          <p class="text-xs mt-2 <?= $overBudget ? 'text-rose-300' : 'text-slate-400' ?>">
            <?php if ($budgetAmount === 0): ?>Belum ada batas pengeluaran.
            <?php elseif ($spentAmount > $budgetAmount): ?>Lebih <?= Helper::rp($spentAmount - $budgetAmount) ?> dari anggaran.
            <?php else: ?>Sisa anggaran <?= Helper::rp($budgetAmount - $spentAmount) ?>.<?php endif ?>
          </p>
          <form method="post" action="<?= Helper::url('/budget') ?>" class="budget-form">
            <?= Helper::field() ?>
            <input type="hidden" name="category_id" value="<?= (int)$budget['id'] ?>">
            <input type="hidden" name="budget_month" value="<?= Helper::e($ym) ?>">
            <label for="budget-<?= (int)$budget['id'] ?>">Anggaran kategori (rupiah)</label>
            <div class="flex gap-2">
              <input id="budget-<?= (int)$budget['id'] ?>" type="number" name="amount" min="0" step="1000" value="<?= $budgetAmount ?>" class="in" required>
              <button class="btn btn-ghost">Simpan</button>
            </div>
          </form>
        </article>
      <?php endforeach ?>
    </div>
  </section>

  <section class="grid lg:grid-cols-[1.6fr_1fr_1fr] gap-4">
    <div class="card min-w-0"><div class="flex flex-wrap justify-between items-start gap-3 mb-3"><div><h2 id="expenseChartTitle" class="font-bold">Pengeluaran bulanan</h2><p id="expenseChartCaption" class="text-xs text-slate-500">Total pengeluaran • 6 bulan terakhir</p></div>
      <div class="flex gap-1 shrink-0"><?php foreach (['monthly' => 'Bulanan', 'weekly' => 'Mingguan', 'daily' => 'Harian'] as $k => $l): ?><button class="navtab <?= $k === 'monthly' ? 'on' : '' ?>" data-period-button="<?= $k ?>"><?= $l ?></button><?php endforeach ?></div></div>
      <p class="text-xs text-slate-500 mb-2">Lihat pergerakan uang berdasarkan waktu dan jenisnya.</p>
      <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
        <div class="flex gap-2 text-xs">
          <button type="button" class="navtab" data-series-button="income" aria-pressed="false"><i class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1"></i>Pemasukan</button>
          <button type="button" class="navtab on" data-series-button="expense" aria-pressed="true"><i class="inline-block w-2 h-2 rounded-full bg-rose-500 mr-1"></i>Pengeluaran</button>
        </div>
        <span id="expenseChartTotal" class="text-[11px] text-slate-400"></span>
      </div>
      <div class="h-52"><?= Chart::cashFlow($cashTrends) ?></div></div>
    <div class="card"><h2 class="font-bold mb-1">Pengeluaran per kategori</h2><p class="text-xs text-slate-500 mb-3">Bulan ini</p>
      <?php if ($cats): ?><div class="h-36"><?= Chart::donut($cats) ?></div><?= Chart::legend($cats) ?><p class="text-[11px] text-slate-500 text-center mt-3">Kategori terbanyak: <b class="text-emerald-400"><?= Helper::e($top) ?></b></p>
      <?php else: ?><p class="text-sm text-slate-500 py-14 text-center">Belum ada pengeluaran bulan ini.</p><?php endif ?></div>
    <div class="card flex flex-col min-w-0"><span class="chip self-start !text-emerald-300 !bg-emerald-500/15">Catatan Transaksi</span>
      <h2 class="font-extrabold text-lg mt-3">Catatan Pilihan</h2>
      <p class="text-xs text-slate-500 mt-1 mb-3">Simpan sampai 3 transaksi penting agar mudah ditemukan.</p>
      <div class="space-y-2">
        <?php foreach ($featured as $note): $income = $note['type'] === 'income'; ?>
          <div class="p-3 rounded-xl bg-black/30 border border-white/5">
            <div class="flex justify-between gap-2 items-center mb-1">
              <span class="text-[10px] <?= $income ? 'text-emerald-400' : 'text-rose-400' ?>"><?= $income ? 'Pemasukan' : 'Pengeluaran' ?> · <?= Helper::e($note['cat']) ?></span>
              <span class="text-[10px] text-slate-500 shrink-0"><?= Helper::tgl($note['trx_date']) ?></span>
            </div>
            <p class="text-xs font-bold text-slate-200 break-words"><?= Helper::e($note['title']) ?></p>
            <?php if ($note['note'] !== '' && $note['note'] !== $note['title']): ?><p class="text-xs text-slate-400 mt-1 whitespace-pre-line break-words"><?= Helper::e($note['note']) ?></p><?php endif ?>
          </div>
        <?php endforeach ?>
        <?php if (!$featured): ?><p class="text-xs text-slate-400 p-3 rounded-xl bg-black/30 border border-white/5">Belum ada catatan pilihan. Pilih ☆ pada transaksi di riwayat untuk menampilkannya di sini.</p><?php endif ?>
      </div>
      <a href="<?= Helper::url('/transactions') ?>" class="mt-auto pt-4 text-xs text-emerald-400 hover:text-emerald-300">Lihat semua transaksi →</a></div>
  </section>

  <section class="grid lg:grid-cols-[1fr_2.4fr] gap-4">
    <div class="card"><div class="flex justify-between items-center gap-2"><h2 class="font-bold">Batas pengeluaran harian</h2>
        <details class="setting-control">
          <summary>Atur</summary>
          <form id="limitForm" method="post" action="<?= Helper::url('/limit') ?>"><?= Helper::field() ?>
            <label for="dailyLimit">Batas per hari (rupiah)</label>
            <input id="dailyLimit" name="limit" type="number" min="0" step="1000" value="<?= $manual ?>" class="in" required>
            <p>Isi 0 untuk menghitung batas secara otomatis.</p>
            <button class="btn">Simpan batas</button>
          </form>
        </details></div>
      <div class="num text-3xl font-extrabold mt-2"><?= Helper::rp($limit) ?></div>
      <p class="text-xs text-slate-500 mt-1"><?= $manual > 0 ? 'Batas yang kamu tentukan' : 'Dihitung otomatis dari sisa uang bulan ini' ?></p>
      <div class="bar mt-3"><i style="width:<?= $limPct ?>%;background:<?= $limPct >= 100 ? '#f43f5e' : ($limPct >= 70 ? '#f59e0b' : '#10b981') ?>"></i></div>
      <div class="mt-3 p-3 rounded-xl bg-black/30 text-xs space-y-1">      <div class="flex justify-between"><span>Sudah dipakai hari ini</span><b class="num"><?= Helper::rp($today) ?></b></div>
      <div class="flex justify-between"><span>Rata-rata per hari</span><b class="num"><?= Helper::rp(round($avg)) ?></b></div></div>
      <p class="text-xs mt-2 <?= $today > $limit ? 'text-rose-400' : 'text-emerald-400' ?>"><?= $today > $limit ? 'Melebihi batas sebesar ' . Helper::rp($today - $limit) : 'Sisa batas hari ini ' . Helper::rp($limit - $today) ?></p></div>
    <div class="card"><div class="flex flex-wrap justify-between items-end gap-2 mb-3"><div><h2 class="font-bold">Saldo per rekening / dompet</h2><p class="text-xs text-slate-500 mt-1">Pilih salah satu untuk mencatat uang masuk.</p></div></div>
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2"><?php foreach ([
        'BCA' => ['BCA', 'wallet-logo-bca'],
        'GoPay' => ['Go', 'wallet-logo-gopay'],
        'OVO' => ['OVO', 'wallet-logo-ovo'],
        'QRIS' => ['QR', 'wallet-logo-qris'],
        'ShopeePay' => ['S', 'wallet-logo-shopeepay'],
        'Cash' => ['Rp', 'wallet-logo-cash'],
      ] as $mt => [$logo, $logoClass]): ?>
        <button type="button" class="wallet-card" data-add-balance="<?= Helper::e($mt) ?>" aria-label="Tambah saldo <?= Helper::e($mt) ?>">
          <span class="wallet-card-top"><span class="wallet-logo <?= $logoClass ?>"><?= $logo ?></span><span class="text-[11px] text-slate-400"><?= Helper::e($mt) ?></span></span>
          <span class="num text-sm font-extrabold mt-3 block <?= ($methods[$mt] ?? 0) < 0 ? 'text-rose-300' : 'text-slate-100' ?>"><?= Helper::rp($methods[$mt] ?? 0) ?></span>
          <span class="wallet-card-action">＋ Tambah saldo</span>
        </button><?php endforeach ?></div></div>
  </section>

  <section class="grid lg:grid-cols-[1fr_2.4fr] gap-4">
    <div class="card" id="wishlist">
      <h2 class="font-extrabold mb-1">Target belanja</h2>
      <p class="text-xs text-slate-500 mb-3">Catat barang yang ingin dibeli dan perkiraan harganya.</p>
      <div class="space-y-2"><?php foreach ($wish as $w): ?>
        <div class="row !p-3"><div class="flex-1 min-w-0"><div class="text-xs font-bold truncate"><?= Helper::e($w['name']) ?></div><div class="text-[11px] text-emerald-400 num"><?= Helper::rp($w['price']) ?></div></div>
          <form method="post" action="<?= Helper::url('/wishlist/delete') ?>"><?= Helper::field() ?><input type="hidden" name="id" value="<?= $w['id'] ?>"><button class="text-slate-500 hover:text-rose-400 text-xs" aria-label="Hapus">✕</button></form></div><?php endforeach ?></div>
      <form method="post" action="<?= Helper::url('/wishlist/store') ?>" class="mt-3 space-y-2"><?= Helper::field() ?>
        <input name="name" class="in !py-2 text-xs" placeholder="Mau beli apa?" required><input name="price" class="in !py-2 text-xs" inputmode="numeric" placeholder="Harga" required>
        <button class="btn btn-ghost w-full">＋ Tambah target belanja</button></form></div>
    <div class="card" id="payday"><div class="flex justify-between items-start mb-4"><div>
      <h2 class="font-extrabold text-lg">Panduan pembagian uang 50/30/20</h2><p class="text-xs text-slate-500 mt-1">Perkiraan pembagian dari uang masuk bulan ini.</p></div>
      <div class="text-xs text-slate-400">Uang masuk: <b class="text-emerald-400 num"><?= Helper::rp($inc) ?></b></div></div>
      <div class="grid md:grid-cols-3 gap-3"><?php foreach ($plan as [$t, $sub, $target, $used, $col, $hint]): $pct = $target > 0 ? min(100, round($used / $target * 100)) : 0; ?>
        <div class="p-4 rounded-2xl bg-black/30 border border-white/5"><div class="flex justify-between text-xs"><b><?= $t ?></b><span class="text-slate-500"><?= $sub ?></span></div>
          <div class="num text-xl font-extrabold my-2"><?= Helper::rp($target) ?></div><div class="bar"><i style="width:<?= $pct ?>%;background:<?= $col ?>"></i></div>
          <p class="text-[11px] text-slate-500 mt-2">Terpakai <?= Helper::rp($used) ?> • <?= $hint ?></p></div><?php endforeach ?></div></div>
  </section>

  <section class="card" id="transactions"><div class="flex flex-wrap justify-between items-center gap-3 mb-4"><div><h2 class="font-extrabold text-lg">Transaksi terbaru</h2><p class="text-xs text-slate-500 mt-1">Periksa catatan uang masuk dan keluar terakhir.</p></div><a class="btn btn-ghost" href="<?= Helper::url('/transactions') ?>">Lihat semua transaksi</a></div>
    <div class="space-y-2"><?php foreach ($recent as $t) trxRow($t); ?>
    <?php if (!$recent): ?><div class="empty-state"><strong>Belum ada transaksi</strong><p>Mulai dengan mencatat uang masuk atau pengeluaran pertamamu.</p><button class="btn mt-3" data-open-modal>＋ Catat transaksi</button></div><?php endif ?></div></section>
</main>
<script>
document.querySelectorAll('[data-period-button]').forEach(function(b){b.onclick=function(){
  var p=b.dataset.periodButton;
  var s=document.querySelector('[data-series-button].on').dataset.seriesButton;
  document.querySelectorAll('#expenseChart [data-period]').forEach(function(g){g.style.display=g.dataset.period===p&&g.dataset.series===s?'':'none'});
  document.querySelectorAll('[data-period-button]').forEach(function(x){x.classList.toggle('on',x===b)});
  updateCashChartText(p,s,b.textContent);
};});
document.querySelectorAll('[data-series-button]').forEach(function(b){b.onclick=function(){
  var s=b.dataset.seriesButton;
  var p=document.querySelector('[data-period-button].on').dataset.periodButton;
  document.querySelectorAll('#expenseChart [data-period]').forEach(function(g){g.style.display=g.dataset.period===p&&g.dataset.series===s?'':'none'});
  document.querySelectorAll('[data-series-button]').forEach(function(x){x.classList.toggle('on',x===b);x.setAttribute('aria-pressed',x===b?'true':'false')});
  updateCashChartText(p,s,document.querySelector('[data-period-button].on').textContent);
};});
function updateCashChartText(period,series,periodLabel){
  var label=series==='income'?'Pemasukan':'Pengeluaran';
  document.getElementById('expenseChartTitle').textContent=label+' '+periodLabel;
  var active=document.querySelector('#expenseChart [data-period="'+period+'"][data-series="'+series+'"]');
  document.getElementById('expenseChartTotal').textContent='Total '+new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(Number(active.dataset.total));
  document.getElementById('expenseChartCaption').textContent={
    monthly:'Total '+label.toLowerCase()+' • 6 bulan terakhir',
    weekly:'Total '+label.toLowerCase()+' • 7 hari terakhir',
    daily:'Total '+label.toLowerCase()+' • hari ini'
  }[period];
}
updateCashChartText('monthly','expense','Bulanan');
</script>
<?php require ROOT . '/views/layouts/footer.php'; ?>
