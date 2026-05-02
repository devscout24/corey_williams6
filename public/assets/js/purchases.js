// ─── Data ──────────────────────────────────────────────────
const allPurchases = [
  { id: 'RCV-001', date: 'Apr 28, 2026', supplier: 'Hakim Group',    items: 12, total: '$1,240.00', status: 'Closed', mode: 'Receive' },
  { id: 'RCV-002', date: 'Apr 29, 2026', supplier: 'Fresh Farms',    items: 5,  total: '$430.50',   status: 'Open',   mode: 'Receive' },
  { id: 'RCV-003', date: 'Apr 29, 2026', supplier: 'Global Traders', items: 8,  total: '$870.00',   status: 'Open',   mode: 'Receive' },
  { id: 'RTV-001', date: 'Apr 27, 2026', supplier: 'Hakim Group',    items: 3,  total: '$210.00',   status: 'Closed', mode: 'Return'  },
  { id: 'RTV-002', date: 'Apr 30, 2026', supplier: 'Fresh Farms',    items: 2,  total: '$95.00',    status: 'Open',   mode: 'Return'  },
  { id: 'RCV-004', date: 'Apr 30, 2026', supplier: 'City Suppliers', items: 20, total: '$3,100.00', status: 'Open',   mode: 'Receive' },
];

let currentMode = 'Receive';
window.currentPercentDiscount = 0;
window.currentFlatDiscount    = 0;

// ─── Render History Table ─────────────────────────────────────
function renderHistory(data) {
  const tbody = document.getElementById('historyBody');
  if (!tbody) return;
  
  if (!data.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#94a3b8;">No records found.</td></tr>';
    const countEl = document.getElementById('historyCount');
    if (countEl) countEl.textContent = 'Showing 0 records';
    return;
  }
  
  tbody.innerHTML = data.map(r => `
    <tr>
      <td><input type="checkbox" class="form-check-input purchases-checkbox"></td>
      <td onclick="openPurchases('${r.id}')"><span class="history-id">${r.id}</span></td>
      <td onclick="openPurchases('${r.id}')">${r.date}</td>
      <td onclick="openPurchases('${r.id}')">${r.supplier}</td>
      <td onclick="openPurchases('${r.id}')" style="text-align:center;">${r.items}</td>
      <td onclick="openPurchases('${r.id}')" style="font-weight:700;color:#1e293b;">${r.total}</td>
      <td onclick="openPurchases('${r.id}')">
        <span class="badge-${r.status.toLowerCase()}">${r.status}</span>
      </td>
      <td style="text-align:right;">
        <div class="d-flex justify-content-end gap-1">
          <button class="btn btn-sm btn-light" style="padding: 2px 8px; color: var(--primary);" onclick="editPurchases('${r.id}')" title="Edit">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-light" style="padding: 2px 8px; color: #ef4444;" onclick="deletePurchases('${r.id}')" title="Delete">
            <i class="bi bi-trash3"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
  
  const countEl = document.getElementById('historyCount');
  if (countEl) countEl.textContent = `Showing ${data.length} record${data.length !== 1 ? 's' : ''}`;
}

// ─── Toggle: Receive / Return (List View) ──────────────────────
function setListMode(mode) {
  currentMode = mode;
  document.getElementById('modeReceive').classList.toggle('active', mode === 'Receive');
  document.getElementById('modeReturn').classList.toggle('active', mode === 'Return');
  document.getElementById('listModeLabel').textContent = `Showing: ${mode === 'Receive' ? 'Purchases' : 'Returns'}`;
  document.getElementById('historyTitle').textContent = mode === 'Receive' ? 'Recent Purchases' : 'Recent Returns';
  
  // Update button names
  const historyBtn = document.getElementById('historyAddBtn');
  if (historyBtn) historyBtn.innerHTML = `<i class="bi bi-plus-lg"></i> ${mode === 'Receive' ? 'Add Purchases' : 'Add Return'}`;
  
  filterAndRender();
}

// ─── Search / Filter ──────────────────────────────────────────
function filterAndRender() {
  const criteria = document.getElementById('searchCriteria').value;
  const query = document.getElementById('historySearchInput').value.trim().toLowerCase();
  let filtered = allPurchases.filter(r => r.mode === currentMode);
  if (query) {
    filtered = filtered.filter(r => String(r[criteria] || '').toLowerCase().includes(query));
  }
  renderHistory(filtered);
}

function searchHistory() { filterAndRender(); }
function clearSearch() {
  document.getElementById('historySearchInput').value = '';
  filterAndRender();
}

// ─── View Switching ───────────────────────────────────────────
function showAddPurchasesScreen() {
  document.getElementById('viewPurchasesList').style.display = 'none';
  document.getElementById('viewAddPurchases').style.display = 'block';
  // Ensure the form mode matches the current list mode
  setPurchasesMode(currentMode);
}

function showPurchasesList() {
  document.getElementById('viewAddPurchases').style.display = 'none';
  document.getElementById('viewPurchasesList').style.display = 'block';
}

function openPurchases(id) {
  showAddPurchasesScreen();
}

function editPurchases(id) {
  console.log('Editing Purchases:', id);
  showAddPurchasesScreen();
}

function deletePurchases(id) {
  if (confirm(`Are you sure you want to delete ${id}?`)) {
    console.log('Deleting Purchases:', id);
    // Logic to remove from allPurchases and re-render
  }
}

// Select All functionality
document.addEventListener('change', e => {
  if (e.target.id === 'selectAllPurchases') {
    const checkboxes = document.querySelectorAll('.purchases-checkbox');
    checkboxes.forEach(cb => cb.checked = e.target.checked);
  }
});

// ─── Form Helpers ─────────────────────────────────────────────
function setPurchasesMode(mode) {
  document.getElementById('modeReceiveForm').classList.toggle('active', mode === 'Receive');
  document.getElementById('modeReturnForm').classList.toggle('active', mode === 'Return');
  document.getElementById('cartModeLabel').textContent = mode === 'Receive' ? '[Purchases]' : '[Return]';
  
  // Update finish button name
  const finishBtn = document.getElementById('finishBtn');
  if (finishBtn) {
    finishBtn.innerHTML = `<i class="bi bi-check-circle"></i> ${mode === 'Receive' ? 'Finish Purchases' : 'Finish Return'}`;
  }
}

function switchFilter(btn, viewId) {
  document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.filter-view').forEach(v => v.style.display = 'none');
  document.getElementById(viewId).style.display = 'block';
}

function toggleGrid() {
  const grid = document.getElementById('filtersCard');
  if (grid) grid.style.display = grid.style.display === 'none' ? 'block' : 'none';
}

// ─── Discount & Totals Logic ──────────────────────────────────
function showDiscountForm(el) {
  el.style.display = 'none';
  el.nextElementSibling.style.display = 'flex';
  el.nextElementSibling.querySelector('input').focus();
}

function hideDiscountForm(btn) {
  const group = btn.closest('.discount-input-group') || btn.parentElement;
  const link = group.previousElementSibling;
  const val = group.querySelector('input').value;

  if (val) {
    const parentRow = btn.closest('.discount-row');
    const labelText = parentRow.querySelector('span:first-child').innerText;
    const isPercent = labelText.includes('Percent');

    link.innerText = isPercent
      ? (val.endsWith('%') ? val : val + '%')
      : '$' + parseFloat(val).toFixed(2);
    link.style.color = 'var(--primary)';
    link.style.borderBottom = 'none';

    if (isPercent) {
      window.currentPercentDiscount = parseFloat(val) || 0;
      
      // Update the Disc % column in the cart table for all items (including Purchases Total row)
      document.querySelectorAll('#cartItems tr').forEach(row => {
        if (row.cells[4]) {
          row.cells[4].innerText = window.currentPercentDiscount + '%';
        }
      });
    } else {
      const amount = parseFloat(val) || 0;
      window.currentFlatDiscount = amount;

      // Show this amount as a row in the cart table
      const tbody   = document.getElementById('cartItems');
      const emptyMsg = document.getElementById('emptyCart');

      // Remove any previous "Purchases Total" row
      const existing = tbody ? tbody.querySelector('.recv-total-row') : null;
      if (existing) existing.remove();

      if (tbody && amount > 0) {
        const tr = document.createElement('tr');
        tr.className = 'recv-total-row';
        tr.innerHTML = `
          <td><button class="btn-remove-item" onclick="this.closest('tr').remove(); window.currentFlatDiscount=0; updateTotals(); document.getElementById('emptyCart').style.display='block';"><i class="bi bi-dash-circle-fill"></i></button></td>
          <td style="text-align:left;"><div class="item-name">Purchases Total</div></td>
          <td style="color:var(--primary);font-weight:700;">$${amount.toFixed(2)}</td>
          <td style="color:var(--primary);font-weight:700;">1</td>
          <td>${window.currentPercentDiscount || 0}%</td>
          <td style="color:var(--primary);font-weight:700;">$${amount.toFixed(2)}</td>`;
        tbody.appendChild(tr);
        if (emptyMsg) emptyMsg.style.display = 'none';
      }
    }
    updateTotals();
  }

  group.style.display = 'none';
  link.style.display = 'inline';
}

function updateTotals() {
  // Sub Total = sum of all cart row totals
  let subTotal = 0;
  document.querySelectorAll('#cartItems tr').forEach(row => {
    const c = row.cells[5];
    if (c) subTotal += parseFloat(c.innerText.replace(/[^0-9.]/g, '')) || 0;
  });

  const percentDisc = window.currentPercentDiscount || 0;

  // Amount Due = Discount all Items by Percent → $ amount
  const amountDue = subTotal * percentDisc / 100;

  // Total = remaining after percent (Sub Total - Amount Due)
  const total = Math.max(0, subTotal - amountDue);

  const subTotalEl  = document.querySelector('.totals-section .total-row:nth-child(1) span:last-child');
  const totalEl     = document.querySelector('.totals-section .total-row:nth-child(3) span:first-child');
  const amountDueEl = document.querySelector('.totals-section .total-row:nth-child(3) span:last-child');

  if (subTotalEl)  subTotalEl.innerText  = '$' + subTotal.toFixed(2);
  if (amountDueEl) amountDueEl.innerText = '$' + amountDue.toFixed(2);
  if (totalEl)     totalEl.innerText     = '$' + total.toFixed(2);
}

function finishPurchases() {
  // Collect data to pass to receipt page
  const items = [];
  document.querySelectorAll('#cartItems tr').forEach(row => {
    const nameEl = row.querySelector('.item-name');
    if (nameEl) {
      items.push({
        name: nameEl.innerText,
        price: row.cells[2].innerText,
        qty: row.cells[3].innerText,
        disc: row.cells[4].innerText,
        total: row.cells[5].innerText
      });
    }
  });

  const subTotalEl = document.querySelector('.totals-section .total-row:nth-child(1) span:last-child');
  const totalEl = document.querySelector('.totals-section .total-row:nth-child(3) span:first-child');
  
  const receiptData = {
    items: items,
    subTotal: subTotalEl ? subTotalEl.innerText : '$0.00',
    total: totalEl ? totalEl.innerText : '$0.00',
    date: new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
  };

  localStorage.setItem('lastReceiptData', JSON.stringify(receiptData));
  
  // Redirect to receipt page
  window.location.href = 'purchases-receipt.html';
}

// ─── Init (script is at bottom of body, DOM already parsed) ──
renderHistory(allPurchases.filter(r => r.mode === 'Receive'));

// Submenu toggle
document.querySelectorAll('[data-toggle="submenu"]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const sub = link.nextElementSibling;
    const arrow = link.querySelector('.nav-arrow');
    if (!sub) return;
    const isOpen = sub.style.display === 'block';
    sub.style.display = isOpen ? 'none' : 'block';
    if (arrow) arrow.style.transform = isOpen ? '' : 'rotate(90deg)';
  });
});

// Enter key search
const searchInput = document.getElementById('historySearchInput');
if (searchInput) {
  searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') searchHistory();
  });
}



