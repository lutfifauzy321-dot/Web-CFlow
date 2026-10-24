<nav id="main-navigation" class="app-nav" aria-label="Menu utama">
  <div class="mobile-nav-heading"><strong>Menu utama</strong><span>Pilih halaman yang ingin kamu buka</span></div>
  <a class="app-nav-link <?= $tab === 'dashboard' ? 'on' : '' ?>" href="<?= Helper::url('/#top') ?>" data-nav-section="top">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 10.5 12 3l8.5 7.5v9a1 1 0 0 1-1 1h-5v-6h-5v6h-5a1 1 0 0 1-1-1z"/></svg><span>Dashboard</span>
  </a>
  <a class="app-nav-link" href="<?= Helper::url('/#monthly-summary') ?>" data-nav-section="monthly-summary">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h16M8 15v-4m4 4V7m4 8V9m4 6v-3"/></svg><span>Ringkasan</span>
  </a>
  <a class="app-nav-link" href="<?= Helper::url('/#category-budgets') ?>" data-nav-section="category-budgets">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6"/><path d="M16 7h3v3"/></svg><span>Anggaran</span>
  </a>
  <a class="app-nav-link" href="<?= Helper::url('/#wishlist') ?>" data-nav-section="wishlist">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 20-1.4-1.3C5.6 14.2 2.5 11.4 2.5 8a4.8 4.8 0 0 1 8.3-3.3L12 6l1.2-1.3A4.8 4.8 0 0 1 21.5 8c0 3.4-3.1 6.2-8.1 10.7Z"/></svg><span>Target belanja</span>
  </a>
  <a class="app-nav-link <?= $tab === 'transaksi' ? 'on' : '' ?>" href="<?= Helper::url('/#transactions') ?>" data-nav-section="transactions">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h10a2 2 0 0 1 2 2v15l-7-3.5-7 3.5v-15a2 2 0 0 1 2-2Z"/><path d="M9 8h6M9 12h6"/></svg><span>Transaksi</span>
  </a>
</nav>
