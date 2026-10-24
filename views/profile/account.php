<?php require ROOT . '/views/layouts/header.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-6 space-y-4">
  <a class="navtab inline-block" href="<?= Helper::url('/profile') ?>">← Pengelolaan Akun</a>
  <section class="card">
    <h1 class="text-xl font-extrabold mb-1">Informasi Profil</h1>
    <p class="text-xs text-slate-400 mb-5">Ubah foto, nama, dan alamat email akun.</p>
    <?php if ($m = Helper::flash()): ?><p class="text-emerald-300 text-sm mb-4 p-3 rounded-xl bg-emerald-500/10"><?= Helper::e($m) ?></p><?php endif ?>
    <form method="post" action="<?= Helper::url('/profile/update') ?>" enctype="multipart/form-data" class="space-y-3">
      <?= Helper::field() ?>
      <label class="block text-xs text-slate-400">Foto profil (JPG, PNG, WEBP; maksimal 3 MB)
        <input type="file" name="avatar" class="in mt-1" accept="image/jpeg,image/png,image/webp">
      </label>
      <label class="block text-xs text-slate-400">Nama
        <input name="name" class="in mt-1" value="<?= Helper::e($u['name']) ?>" required maxlength="100" autocomplete="name">
      </label>
      <label class="block text-xs text-slate-400">Email
        <input type="email" name="email" class="in mt-1" value="<?= Helper::e($u['email']) ?>" required maxlength="150" autocomplete="email">
      </label>
      <button class="btn">Simpan profil</button>
    </form>
  </section>
</main>
<?php require ROOT . '/views/layouts/footer.php'; ?>
