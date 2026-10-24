(() => {
  const q = (s, r = document) => r.querySelector(s);
  const modal = q('#modal'), form = q('#trxForm');
  window.toast = m => { const t = document.createElement('div'); t.id = 'toast'; t.textContent = m; document.body.append(t); setTimeout(() => t.remove(), 3200); };
  if (window.CFLOW?.flash) toast(CFLOW.flash);
  document.querySelectorAll('form[data-confirm]').forEach(f => f.onsubmit = e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  const navLinks = [...document.querySelectorAll('.app-nav-link[data-nav-section]')];
  const nav = q('#main-navigation'), navToggle = q('[data-nav-toggle]');
  const pendingNavSectionKey = 'cflow-pending-nav-section';
  let scrollAnimationFrame = 0;
  const scrollToTop = () => {
    cancelAnimationFrame(scrollAnimationFrame);
    const startY = window.scrollY;
    if (!startY) return;
    const startedAt = performance.now();
    const duration = 700;
    const animate = now => {
      const progress = Math.min((now - startedAt) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      window.scrollTo({ top: startY * (1 - eased), behavior: 'instant' });
      if (progress < 1) scrollAnimationFrame = requestAnimationFrame(animate);
      else scrollAnimationFrame = 0;
    };
    scrollAnimationFrame = requestAnimationFrame(animate);
  };
  const scrollToSection = target => {
    const behavior = matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    if (target.id === 'top') {
      scrollToTop();
      return;
    }
    target.scrollIntoView({ behavior, block: 'start' });
  };
  const setNavOpen = open => {
    if (!nav || !navToggle) return;
    nav.classList.toggle('is-open', open);
    navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    navToggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
  };
  const currentRoute = () => new URL(location.href).searchParams.get('r') || '';
  const updateActiveNav = () => {
    if (!navLinks.length) return;
    const hash = location.hash.slice(1);
    const section = navLinks.some(link => link.dataset.navSection === hash)
      ? hash
      : (currentRoute() === 'transactions' ? 'transactions' : 'top');
    navLinks.forEach(link => {
      const active = link.dataset.navSection === section;
      link.classList.toggle('on', active);
      if (active) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    });
  };
  updateActiveNav();
  const pendingNavSection = sessionStorage.getItem(pendingNavSectionKey);
  if (pendingNavSection) {
    sessionStorage.removeItem(pendingNavSectionKey);
    const target = document.getElementById(pendingNavSection);
    if (target) {
      const destination = new URL(location.href);
      destination.hash = pendingNavSection;
      history.replaceState(null, '', destination.href);
      updateActiveNav();
      requestAnimationFrame(() => scrollToSection(target));
    }
  }
  addEventListener('popstate', updateActiveNav);
  addEventListener('hashchange', updateActiveNav);
  navToggle?.addEventListener('click', () => setNavOpen(navToggle.getAttribute('aria-expanded') !== 'true'));
  document.addEventListener('click', e => {
    if (nav?.classList.contains('is-open') && !nav.contains(e.target) && !navToggle?.contains(e.target)) setNavOpen(false);
  });
  addEventListener('keydown', e => {
    if (e.key === 'Escape' && nav?.classList.contains('is-open')) {
      setNavOpen(false);
      navToggle?.focus();
    }
  });
  addEventListener('resize', () => {
    if (innerWidth > 1100) setNavOpen(false);
  });
  nav?.addEventListener('click', e => {
    const link = e.target.closest('a[data-nav-section]');
    if (!link) return;
    setNavOpen(false);
    const destination = new URL(link.href, location.href);
    const target = document.getElementById(link.dataset.navSection);
    if (destination.origin !== location.origin) return;
    if (target) {
      e.preventDefault();
      if (link.dataset.navSection === 'top') {
        history.replaceState(null, '', `${location.pathname}${location.search}#top`);
      } else if (location.href !== destination.href) {
        history.pushState(null, '', destination.href);
      }
      updateActiveNav();
      scrollToSection(target);
      return;
    }
    if (!destination.hash) return;
    e.preventDefault();
    sessionStorage.setItem(pendingNavSectionKey, link.dataset.navSection);
    destination.hash = '';
    location.assign(destination.href);
  });
  if (!modal) return;

  const fmt = v => String(v).replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  function syncType(keep) {
    const t = q('input[name=type]:checked').value, sel = q('#category');
    [...sel.options].forEach(o => { o.hidden = o.disabled = o.dataset.type !== t; });
    if (!keep || sel.selectedOptions[0]?.disabled) sel.value = [...sel.options].find(o => !o.hidden)?.value;
    q('#noteLabelText').textContent = t === 'income'
      ? 'Deskripsi / sumber pemasukan (opsional)'
      : 'Deskripsi / alasan pengeluaran (opsional)';
    q('#trxNote').placeholder = t === 'income'
      ? 'Contoh: Gaji bulan Oktober atau hasil freelance desain'
      : 'Contoh: Beli barang ini karena dibutuhkan atau keadaan darurat';
  }
  function open(d, presetMethod = '') {
    form.reset(); q('#preview').classList.add('hidden');
    form.elements['trx_id'].value = d?.id || ''; q('#modalTitle').textContent = d ? 'Edit Transaksi' : 'Catat Transaksi'; q('#saveBtn').textContent = d ? 'Simpan perubahan' : 'Simpan transaksi';
    q(`input[name=type][value=${d?.type || 'expense'}]`).checked = true; syncType();
    if (d) {
      const el = n => form.elements[n];
      q('#amount').value = fmt(d.amount); el('title').value = d.title; el('description').value = d.note; el('category_id').value = d.category_id; el('method').value = d.method;
      el('tags').value = d.tags.replace(/#/g, '').trim(); el('trx_date').value = d.trx_date.replace(' ', 'T').slice(0, 16);
    }
    if (presetMethod) {
      q('input[name=type][value=income]').checked = true; syncType();
      form.elements['method'].value = presetMethod;
      form.elements['title'].value = `Tambah saldo ${presetMethod}`;
      q('#modalTitle').textContent = `Tambah saldo ${presetMethod}`;
      q('#noteLabelText').textContent = 'Deskripsi / sumber saldo (opsional)';
      q('#trxNote').placeholder = `Contoh: Isi saldo ${presetMethod} dari rekening atau sumber dana`;
    }
    modal.classList.add('open'); q('#amount').focus();
  }
  const close = () => modal.classList.remove('open');
  document.addEventListener('click', e => {
    const wallet = e.target.closest('[data-add-balance]');
    if (wallet) { open(null, wallet.dataset.addBalance); return; }
    if (e.target.closest('[data-open-modal]')) open();
    const b = e.target.closest('[data-edit]'); if (b) open(JSON.parse(b.dataset.edit));
    if (e.target.closest('[data-close-modal]') || e.target === modal) close();
  });
  addEventListener('keydown', e => e.key === 'Escape' && close());
  document.querySelectorAll('input[name=type]').forEach(r => r.onchange = () => syncType());
  q('#amount').addEventListener('input', e => e.target.value = fmt(e.target.value));

  // kompres foto di browser (maks 1600px) biar upload cepat & nggak ditolak server
  q('#receipt').addEventListener('change', async e => {
    const f = e.target.files[0], img = q('#preview'); if (!f) return img.classList.add('hidden');
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { e.target.value = ''; img.classList.add('hidden'); return toast('Format harus JPG, PNG, atau WEBP'); }
    try {
      const bmp = await createImageBitmap(f), s = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
      const c = document.createElement('canvas'); c.width = Math.round(bmp.width * s); c.height = Math.round(bmp.height * s);
      const x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(bmp, 0, 0, c.width, c.height);
      const blob = await new Promise(r => c.toBlob(r, 'image/jpeg', .82)), dt = new DataTransfer();
      dt.items.add(new File([blob], 'bukti.jpg', { type: 'image/jpeg' })); e.target.files = dt.files;
    } catch (_) { if (f.size > 3 * 1024 * 1024) { e.target.value = ''; return toast('Foto maksimal 3 MB'); } }
    img.src = URL.createObjectURL(e.target.files[0]); img.classList.remove('hidden');
  });
  form.addEventListener('submit', () => { q('#saveBtn').disabled = true; q('#saveBtn').textContent = 'Menyimpan...'; });
})();
