<?php require ROOT . '/views/layouts/header.php'; $mode = isset($_GET['daftar']) ? 'daftar' : 'masuk'; ?>
<main class="min-h-screen grid place-items-center p-4">
  <div class="card max-w-sm w-full !p-8">
    <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-500 grid place-items-center text-2xl text-black mb-4">⚡</div>
    <h1 class="text-2xl font-extrabold text-center">cflow</h1>
    <p class="text-sm text-slate-400 mt-2 mb-5 text-center">Catat uang masuk dan keluar, lalu pahami ke mana uangmu pergi.</p>
    <?php if ($m = Helper::flash()): ?><p class="text-rose-300 text-sm mb-3 p-3 rounded-xl bg-rose-500/10"><?= Helper::e($m) ?></p><?php endif ?>
    <div class="seg flex gap-1 p-1 rounded-xl bg-black/30 mb-3"><label><input type="radio" name="tab" value="masuk" <?= $mode == 'masuk' ? 'checked' : '' ?>><span>Masuk</span></label><label><input type="radio" name="tab" value="daftar" <?= $mode == 'daftar' ? 'checked' : '' ?>><span>Buat akun</span></label></div>
    <form id="fMasuk" method="post" action="<?= Helper::url('/login') ?>" class="space-y-3 <?= $mode == 'daftar' ? 'hidden' : '' ?>"><?= Helper::field() ?>
      <label class="login-field">Email
        <input type="email" name="email" class="in mt-1" placeholder="nama@email.com" required autocomplete="email">
      </label>
      <label class="login-field">Password
        <input type="password" name="password" class="in mt-1" placeholder="Masukkan password" required autocomplete="current-password">
      </label>
      <button class="btn w-full !py-3">Masuk</button></form>
    <form id="fDaftar" method="post" action="<?= Helper::url('/register') ?>" class="space-y-3 <?= $mode == 'masuk' ? 'hidden' : '' ?>"><?= Helper::field() ?>
      <label class="login-field">Nama
        <input name="name" class="in mt-1" placeholder="Nama lengkap" required maxlength="100" autocomplete="name">
      </label>
      <label class="login-field">Email
        <input type="email" name="email" class="in mt-1" placeholder="nama@email.com" required autocomplete="email">
      </label>
      <label class="login-field">Password <span class="font-normal text-slate-500">(minimal 6 karakter)</span>
        <input type="password" name="password" class="in mt-1" placeholder="Buat password" required minlength="6" autocomplete="new-password">
      </label>
      <button class="btn w-full !py-3">Buat akun</button></form>
  </div>
</main>
<script>document.querySelectorAll('input[name=tab]').forEach(r=>r.onchange=()=>{const d=r.value==='daftar';fDaftar.classList.toggle('hidden',!d);fMasuk.classList.toggle('hidden',d)})</script>
<?php require ROOT . '/views/layouts/footer.php'; ?>
