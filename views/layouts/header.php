<?php
$tab = $tab ?? 'dashboard';
if (!function_exists('trxRow')) {
function trxRow(array $t, string $back = '/') { $in = $t['type'] === 'income'; ?>
  <div class="row">
    <?php if ($t['receipt']): ?><a href="<?= Helper::url('/uploads/receipts/' . $t['receipt']) ?>" target="_blank" rel="noopener" title="Lihat bukti"><img src="<?= Helper::url('/uploads/receipts/' . $t['receipt']) ?>" class="w-11 h-11 rounded-xl object-cover" alt="Bukti"></a>
    <?php else: ?><div class="w-11 h-11 rounded-xl grid place-items-center text-lg <?= $in ? 'bg-emerald-500/15' : 'bg-rose-500/15' ?>"><?= $t['icon'] ?></div><?php endif ?>
    <div class="min-w-0 flex-1">
      <div class="font-bold text-sm break-words"><?= Helper::e($t['title']) ?> <span class="chip ml-1"><?= Helper::e($t['method']) ?></span></div>
      <?php if ($t['note'] !== '' && $t['note'] !== $t['title']): ?><p class="text-xs text-slate-300 mt-1 break-words whitespace-pre-line"><?= Helper::e($t['note']) ?></p><?php endif ?>
      <div class="text-[11px] text-slate-400 truncate"><?= Helper::e($t['cat']) ?> • <?= Helper::tgl($t['trx_date']) ?> <span class="text-emerald-400"><?= Helper::e($t['tags']) ?></span></div>
    </div>
    <div class="num font-extrabold text-sm whitespace-nowrap <?= $in ? 'text-emerald-400' : 'text-rose-400' ?>"><?= $in ? '+' : '–' ?> <?= Helper::rp($t['amount']) ?></div>
    <button type="button" class="text-slate-500 hover:text-emerald-400 text-xs px-1" aria-label="Edit" data-edit='<?= Helper::e(json_encode(array_intersect_key($t, array_flip(['id', 'type', 'amount', 'category_id', 'method', 'title', 'note', 'tags', 'trx_date'])))) ?>'>✎</button>
    <form method="post" action="<?= Helper::url('/transactions/feature') ?>"><?= Helper::field() ?>
      <input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="featured" value="<?= !empty($t['featured']) ? '0' : '1' ?>"><input type="hidden" name="back" value="<?= Helper::e($back) ?>">
      <button class="text-xs px-1 whitespace-nowrap <?= !empty($t['featured']) ? 'text-emerald-400' : 'text-slate-500 hover:text-emerald-400' ?>" aria-label="<?= !empty($t['featured']) ? 'Batalkan pilihan' : 'Pilih untuk catatan' ?>" title="<?= !empty($t['featured']) ? 'Tampil di Catatan Pilihan' : 'Tampilkan di Catatan Pilihan' ?>"><?= !empty($t['featured']) ? '★ Dipilih' : '☆ Pilih' ?></button>
    </form>
    <form method="post" action="<?= Helper::url('/transactions/delete') ?>" data-confirm="Hapus transaksi ini?"><?= Helper::field() ?>
      <input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="back" value="<?= Helper::e($back) ?>">
      <button class="text-slate-500 hover:text-rose-400 text-xs px-1" aria-label="Hapus">✕</button></form>
  </div>
<?php } } ?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>cflow — pencatat keuangan pribadi</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<link rel="stylesheet" href="<?= Helper::url('/assets/css/custom.css') ?>">
</head><body>
<?php if (!empty($u)): ?>
<header class="sticky top-0 z-40 border-b border-white/5 bg-[#070b12]/80 backdrop-blur-xl">
<div class="header-inner max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
  <a href="<?= Helper::url('/#top') ?>" class="brand-link" aria-label="cflow — Dashboard"><span class="brand-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.2 2.5 5.8 13h5l-.8 8.5L18.2 11h-5.1l.1-8.5Z" fill="currentColor"/></svg></span><span>cflow</span></a>
  <?php include ROOT . '/views/layouts/sidebar.php' ?>
  <div class="header-actions flex items-center gap-2">
    <button class="btn header-add-button" data-open-modal aria-label="Catat transaksi"><span aria-hidden="true">＋</span><span class="header-add-label">Catat transaksi</span></button>
    <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-label="Buka menu navigasi" aria-controls="main-navigation" aria-expanded="false">
      <svg class="menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      <svg class="close-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
    </button>
    <details class="profile-menu">
      <summary class="profile-menu-trigger" aria-label="Menu profil" title="Menu profil">
        <?php if ($u['avatar']): ?><img src="<?= Helper::e(Helper::avatarUrl($u['avatar'])) ?>" class="w-8 h-8 rounded-lg object-cover" referrerpolicy="no-referrer" alt="Foto profil"><?php else: ?><span class="w-8 h-8 rounded-lg bg-slate-700 grid place-items-center text-xs"><?= Helper::e(mb_substr($u['name'], 0, 1)) ?></span><?php endif ?>
      </summary>
      <div class="profile-menu-panel">
        <div class="profile-menu-account"><b><?= Helper::e($u['name']) ?></b><span><?= Helper::e($u['email']) ?></span></div>
        <a href="<?= Helper::url('/profile/account') ?>">Informasi Profil</a>
        <a href="<?= Helper::url('/profile/security') ?>">Keamanan</a>
        <form method="post" action="<?= Helper::url('/logout') ?>" onsubmit="return confirm('Yakin ingin keluar dari akun?')">
          <?= Helper::field() ?>
          <button type="submit">Log out</button>
        </form>
      </div>
    </details>
  </div>
</div>
</header>
<?php endif ?>
