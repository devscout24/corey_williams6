/**
 * Purchases list — behavior ported from corey-dashboard/pages/js/purchases.js
 * Loads rows from Laravel JSON API (same-origin session).
 */
(function () {
  const cfg = window.PURCHASES_PAGE_CONFIG || {};
  const historyUrl = cfg.historyUrl || '';
  let currentMode = cfg.initialMode === 'Return' ? 'Return' : 'Receive';

  const TABLE_COLS = 10;

  function el(id) {
    return document.getElementById(id);
  }

  function setListMode(mode) {
    currentMode = mode;
    const btnRecv = el('modeReceive');
    const btnRet = el('modeReturn');
    if (btnRecv) btnRecv.classList.toggle('active', mode === 'Receive');
    if (btnRet) btnRet.classList.toggle('active', mode === 'Return');

    const label = el('listModeLabel');
    if (label) {
      label.textContent = 'Showing: ' + (mode === 'Receive' ? 'Purchases' : 'Returns');
    }

    const title = el('historyTitle');
    if (title) {
      title.textContent = mode === 'Receive' ? 'Recent Purchases' : 'Recent Returns';
    }

    const ph = el('historySearchInput');
    if (ph) {
      ph.placeholder = mode === 'Receive' ? 'Search purchases…' : 'Search returns…';
    }

    loadHistoryFromApi();
  }

  function typePillClass(docType) {
    const t = (docType || 'receive').toLowerCase();
    if (t === 'return') return 'purchase-type-pill purchase-type-pill-return';
    if (t === 'transfer') return 'purchase-type-pill purchase-type-pill-transfer';
    return 'purchase-type-pill purchase-type-pill-receive';
  }

  function renderHistory(rows) {
    const tbody = el('historyBody');
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        TABLE_COLS +
        '" style="text-align:center;padding:20px;color:#94a3b8;">No records found.</td></tr>';
      const countEl = el('historyCount');
      if (countEl) countEl.textContent = 'Showing 0 records';
      return;
    }

    tbody.innerHTML = rows
      .map(function (r) {
        const badgeClass = r.status_tone === 'open' ? 'badge-open' : r.status_tone === 'neutral' ? 'badge-neutral' : 'badge-closed';
        const rid = r.receiving_id;
        const code = r.internal_code || 'RCV-' + String(rid).padStart(8, '0');
        const docType = (r.type || 'receive').toString();
        const typeClass = typePillClass(docType);
        return (
          '<tr>' +
          '<td><input type="checkbox" class="form-check-input purchases-checkbox"></td>' +
          '<td><span class="history-id" title="Receiving #' +
          rid +
          '">' +
          escapeHtml(code) +
          '</span></td>' +
          '<td><span class="' +
          typeClass +
          '">' +
          escapeHtml(docType) +
          '</span></td>' +
          '<td>' +
          escapeHtml(r.date) +
          '</td>' +
          '<td>' +
          escapeHtml(r.supplier) +
          '</td>' +
          '<td style="text-align:center;">' +
          r.items +
          '</td>' +
          '<td class="history-total-cell">' +
          escapeHtml(r.total) +
          '</td>' +
          '<td><span class="purchases-history-meta">' +
          (r.source ? escapeHtml(r.source) : '—') +
          (r.reference_id ? ' (' + escapeHtml(r.reference_id) + ')' : '') +
          '</span></td>' +
          '<td><span class="' +
          badgeClass +
          '">' +
          escapeHtml(r.status_label) +
          '</span></td>' +
          '<td style="text-align:right;">' +
          '<div class="d-flex justify-content-end gap-1">' +
          '<button type="button" class="btn btn-sm btn-light" style="padding:2px 8px;color:var(--primary);" data-act="edit" data-id="' +
          rid +
          '" title="Edit"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="btn btn-sm btn-light" style="padding:2px 8px;color:#ef4444;" data-act="del" data-id="' +
          rid +
          '" title="Delete"><i class="bi bi-trash3"></i></button>' +
          '</div></td>' +
          '</tr>'
        );
      })
      .join('');

    const countEl = el('historyCount');
    if (countEl) {
      countEl.textContent = 'Showing ' + rows.length + ' record' + (rows.length !== 1 ? 's' : '');
    }
  }

  function escapeHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  async function loadHistoryFromApi() {
    const tbody = el('historyBody');
    if (!tbody || !historyUrl) return;

    tbody.innerHTML =
      '<tr><td colspan="' +
      TABLE_COLS +
      '" style="text-align:center;padding:20px;color:#94a3b8;">Loading…</td></tr>';

    const criteria = el('searchCriteria') ? el('searchCriteria').value : 'id';
    const q = el('historySearchInput') ? el('historySearchInput').value.trim() : '';
    const type = currentMode === 'Return' ? 'return' : 'receive';

    const params = new URLSearchParams({ type: type, criteria: criteria, q: q });

    try {
      const res = await fetch(historyUrl + '?' + params.toString(), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Request failed');
      }
      renderHistory(data.items || []);
    } catch (e) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        TABLE_COLS +
        '" style="text-align:center;padding:20px;color:#ef4444;">Could not load history.</td></tr>';
      const countEl = el('historyCount');
      if (countEl) countEl.textContent = '';
    }
  }

  function searchHistory() {
    loadHistoryFromApi();
  }

  function clearSearch() {
    const inp = el('historySearchInput');
    if (inp) inp.value = '';
    loadHistoryFromApi();
  }

  window.purchasesSetListMode = setListMode;
  window.purchasesSearchHistory = searchHistory;
  window.purchasesClearSearch = clearSearch;

  document.addEventListener('DOMContentLoaded', function () {
    if (!historyUrl) return;

    if (cfg.initialMode === 'Return') {
      setListMode('Return');
    } else {
      setListMode('Receive');
    }

    document.addEventListener('change', function (e) {
      if (e.target && e.target.id === 'selectAllPurchases') {
        document.querySelectorAll('.purchases-checkbox').forEach(function (cb) {
          cb.checked = e.target.checked;
        });
      }
    });

    const searchInput = el('historySearchInput');
    if (searchInput) {
      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          searchHistory();
        }
      });
    }
  });
})();
