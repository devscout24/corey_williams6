/**
 * Dashboard Main JS
 */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Sidebar Toggle (Mobile) ── */
  const toggleBtn    = document.querySelector('.sidebar-toggle');
  const sidebar      = document.querySelector('.sidebar');
  const overlay      = document.querySelector('.sidebar-overlay');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
    document.body.style.overflow = '';
  }

  if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
  if (overlay)   overlay.addEventListener('click', closeSidebar);

  /* ── Nav Dropdowns ── */
  document.querySelectorAll('.nav-link[data-toggle="submenu"]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const parent = link.closest('.nav-item');
      const isOpen = parent.classList.contains('open');

      // Close all
      document.querySelectorAll('.nav-item.open').forEach(item => {
        item.classList.remove('open');
      });

      if (!isOpen) parent.classList.add('open');
    });
  });

  /* ── Active Nav Link ── */
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-link[href]').forEach(link => {
    const href = link.getAttribute('href').split('/').pop();
    if (href === currentPage) {
      link.classList.add('active');
      // open parent submenu if nested
      const parentItem = link.closest('.nav-submenu')?.closest('.nav-item');
      if (parentItem) parentItem.classList.add('open');
    }
  });

  /* ── Init Charts (if page has canvas#salesChart) ── */
  const salesCanvas = document.getElementById('salesChart');
  if (salesCanvas && window.Chart) {
    initSalesChart(salesCanvas);
  }

  /* ── Tooltip init (Bootstrap) ── */
  const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipEls.forEach(el => new bootstrap.Tooltip(el));

  /* ── Dropdown init (Bootstrap) ── */
  const dropdownEls = document.querySelectorAll('[data-bs-toggle="dropdown"]');
  dropdownEls.forEach(el => new bootstrap.Dropdown(el));

});

/* ── Sales Chart ── */
function initSalesChart(canvas) {
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const data   = [120, 145, 130, 155, 140, 170, 165, 180, 160, 175, 185, 190];

  const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 200);
  gradient.addColorStop(0, 'rgba(37,99,235,.18)');
  gradient.addColorStop(1, 'rgba(37,99,235,.00)');

  new Chart(canvas, {
    type: 'line',
    data: {
      labels: months,
      datasets: [{
        label: 'Total Sales',
        data: data,
        borderColor: '#2563EB',
        borderWidth: 2.5,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: '#2563EB',
        fill: true,
        backgroundColor: gradient,
        tension: 0.45,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1E293B',
          titleColor: '#94A3B8',
          bodyColor: '#fff',
          padding: 10,
          cornerRadius: 8,
          displayColors: false,
          callbacks: {
            label: ctx => `$${ctx.parsed.y.toFixed(2)}`
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          border: { display: false },
          ticks: { color: '#94A3B8', font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" } }
        },
        y: {
          grid: { color: '#F1F5F9', drawBorder: false },
          border: { display: false, dash: [4,4] },
          ticks: {
            color: '#94A3B8',
            font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
            callback: v => v
          },
          min: 0,
          max: 200,
        }
      }
    }
  });
}

/* ── Utility: format currency ── */
function formatCurrency(val, symbol = '$') {
  return symbol + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2 });
}
