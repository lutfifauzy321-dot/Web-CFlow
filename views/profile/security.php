<?php require ROOT . '/views/layouts/header.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-6 space-y-4">
  <a class="navtab inline-block" href="<?= Helper::url('/profile') ?>">← Pengelolaan Akun</a>
  <section class="card">
    <h1 class="text-xl font-extrabold mb-1">Keamanan Akun</h1>
    <p class="text-xs text-slate-400 mb-4"><?= $u['password_hash'] ? 'Ganti password dengan memasukkan password saat ini.' : 'Akun Google belum memiliki password. Kamu bisa membuat password untuk login dengan email.' ?></p>
    <?php if ($m = Helper::flash()): ?><p class="text-emerald-300 text-sm mb-4 p-3 rounded-xl bg-emerald-500/10"><?= Helper::e($m) ?></p><?php endif ?>
    <form method="post" action="<?= Helper::url('/profile/password') ?>" class="space-y-3">
      <?= Helper::field() ?>
      <?php if ($u['password_hash']): ?>
        <label class="block text-xs text-slate-400">Password saat ini
          <input type="password" name="current_password" class="in mt-1" required autocomplete="current-password">
        </label>
      <?php endif ?>
      <label class="block text-xs text-slate-400">Password baru
        <input type="password" name="password" class="in mt-1" required minlength="6" autocomplete="new-password">
      </label>
      <label class="block text-xs text-slate-400">Ulangi password baru
        <input type="password" name="password_confirmation" class="in mt-1" required minlength="6" autocomplete="new-password">
      </label>
      <button class="btn btn-ghost">Perbarui password</button>
    </form>
  </section>
</main>
<?php require ROOT . '/views/layouts/footer.php'; ?>
