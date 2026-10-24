<?php require ROOT . '/views/layouts/header.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-6 space-y-4">
  <section class="card">
    <div class="flex items-center gap-4">
      <?php if ($u['avatar']): ?><img src="<?= Helper::e(Helper::avatarUrl($u['avatar'])) ?>" class="w-14 h-14 rounded-2xl object-cover" referrerpolicy="no-referrer" alt="Foto profil">
      <?php else: ?><div class="w-14 h-14 rounded-2xl bg-slate-700 grid place-items-center text-xl font-bold"><?= Helper::e(mb_substr($u['name'], 0, 1)) ?></div><?php endif ?>
      <div><h1 class="text-xl font-extrabold">Pengelolaan Akun</h1><p class="text-xs text-slate-400"><?= Helper::e($u['name']) ?> · <?= Helper::e($u['email']) ?></p></div>
    </div>
  </section>
  <?php if ($m = Helper::flash()): ?><p class="text-emerald-300 text-sm p-3 rounded-xl bg-emerald-500/10"><?= Helper::e($m) ?></p><?php endif ?>
  <section class="grid sm:grid-cols-2 gap-4">
    <a href="<?= Helper::url('/profile/account') ?>" class="card block hover:border-emerald-500/50 transition">
      <span class="text-2xl">👤</span><h2 class="font-bold mt-3">Informasi Profil</h2>
      <p class="text-xs text-slate-400 mt-1">Ubah nama dan alamat email akun.</p>
    </a>
    <a href="<?= Helper::url('/profile/security') ?>" class="card block hover:border-emerald-500/50 transition">
      <span class="text-2xl">🔒</span><h2 class="font-bold mt-3">Keamanan</h2>
      <p class="text-xs text-slate-400 mt-1">Atur atau perbarui password akun.</p>
    </a>
  </section>
  <section class="card flex flex-wrap items-center justify-between gap-3">
    <div><h2 class="font-bold">Keluar dari akun</h2><p class="text-xs text-slate-400">Sesi akun ini akan diakhiri di perangkat ini.</p></div>
    <form method="post" action="<?= Helper::url('/logout') ?>" onsubmit="return confirm('Yakin ingin keluar dari akun?')">
      <?= Helper::field() ?>
      <button class="btn !bg-rose-500 !text-white">Log out</button>
    </form>
  </section>
</main>
<?php require ROOT . '/views/layouts/footer.php'; ?>
