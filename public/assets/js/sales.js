function switchFilter(el, viewId) {
    document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.filter-view').forEach(view => view.style.display = 'none');
    document.getElementById(viewId).style.display = 'block';
  }

  function toggleGrid() {
    const grid = document.getElementById('filtersCard');
    grid.style.display = (grid.style.display === 'none') ? 'block' : 'none';
  }

  function switchPayment(el) {
    document.querySelectorAll('.btn-pay-method').forEach(btn => btn.classList.remove('active'));
    el.classList.add('active');
  }

  function showDiscountForm(el) {
    el.style.display = 'none';
    el.nextElementSibling.style.display = 'flex';
    el.nextElementSibling.querySelector('input').focus();
  }

  function hideDiscountForm(btn) {
    const group = btn.closest('.discount-input-group');
    const link = group.previousElementSibling;
    const val = group.querySelector('input').value;
    if (val) {
      link.innerText = val + (link.innerText.includes('Percent') ? '%' : '');
      link.style.color = 'var(--primary)';
      link.style.borderBottom = 'none';
    }
    group.style.display = 'none';
    link.style.display = 'inline';
  }




