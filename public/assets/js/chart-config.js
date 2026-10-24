(() => {
  const D = window.CFLOW; if (!D) return;
  if (!window.Chart) { const c = document.querySelector('#flowChart'); if (c) c.parentNode.innerHTML = '<p class="text-sm text-slate-500 text-center pt-16">Grafik gagal dimuat. Refresh halaman.</p>'; return; }
  const q = s => document.querySelector(s);
  Chart.defaults.color = '#94a3b8'; Chart.defaults.font.family = "'Plus Jakarta Sans',sans-serif"; Chart.defaults.font.size = 10;
  const fmt = v => v >= 1e6 ? (v / 1e6) + 'jt' : v >= 1e3 ? (v / 1e3) + 'rb' : v;
  let flow;
  const build = mode => {
    flow?.destroy(); const bar = mode === 'bar', area = mode === 'area';
    const ds = (label, data, c) => ({ label, data, borderColor: c, backgroundColor: bar ? c : c + '30', borderRadius: 6, fill: area, tension: .4 });
    flow = new Chart(q('#flowChart'), { type: bar ? 'bar' : 'line', data: { labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
      datasets: [ds('Pemasukan', D.weekly.income, '#10b981'), ds('Pengeluaran', D.weekly.expense, '#f43f5e')] },
      options: { maintainAspectRatio: false, plugins: { legend: { labels: { boxWidth: 10 } } },
        scales: { x: { grid: { display: false } }, y: { grid: { color: 'rgba(148,163,184,.08)' }, ticks: { callback: fmt } } } } });
  };
  build('bar');
  document.querySelectorAll('[data-chart]').forEach(b => b.onclick = () => {
    build(b.dataset.chart); document.querySelectorAll('[data-chart]').forEach(x => x.classList.toggle('on', x === b));
  });
  if (D.cats.length && q('#catChart')) new Chart(q('#catChart'), { type: 'doughnut', data: { labels: D.cats.map(c => c.name),
    datasets: [{ data: D.cats.map(c => +c.a), backgroundColor: ['#8b5cf6', '#3b82f6', '#10b981', '#f472b6', '#f59e0b', '#06b6d4'], borderWidth: 0 }] },
    options: { cutout: '72%', plugins: { legend: { display: false } } } });
})();
