// Tab switching
    // Time Picker Logic
    function initTimePicker(containerId, initialTime) {
      const container = document.getElementById(containerId);
      if (!container) return;

      const input = container.querySelector('.sc-time-picker-input');
      const dropdown = container.querySelector('.sc-time-picker-dropdown');
      const hVal = container.querySelector('.hour-val');
      const mVal = container.querySelector('.min-val');
      const ampmBtn = container.querySelector('.sc-time-picker-ampm');

      let [time, period] = initialTime.split(' ');
      let [h, m] = time.split(':');

      const updateDisplay = () => {
        hVal.textContent = h.padStart(2, '0');
        mVal.textContent = m.padStart(2, '0');
        ampmBtn.textContent = period.toUpperCase();
        input.value = `${h}:${m.padStart(2, '0')} ${period.toLowerCase()}`;
      };

      container.querySelector('.h-up').onclick = () => {
        let val = parseInt(h);
        h = val >= 12 ? '01' : (val + 1).toString().padStart(2, '0');
        updateDisplay();
      };
      container.querySelector('.h-down').onclick = () => {
        let val = parseInt(h);
        h = val <= 1 ? '12' : (val - 1).toString().padStart(2, '0');
        updateDisplay();
      };
      container.querySelector('.m-up').onclick = () => {
        let val = parseInt(m);
        m = val >= 59 ? '00' : (val + 1).toString().padStart(2, '0');
        updateDisplay();
      };
      container.querySelector('.m-down').onclick = () => {
        let val = parseInt(m);
        m = val <= 0 ? '59' : (val - 1).toString().padStart(2, '0');
        updateDisplay();
      };
      ampmBtn.onclick = () => {
        period = period.toLowerCase() === 'am' ? 'pm' : 'am';
        updateDisplay();
      };

      input.onclick = (e) => {
        e.stopPropagation();
        document.querySelectorAll('.sc-time-picker-dropdown').forEach(d => {
          if (d !== dropdown) d.classList.remove('active');
        });
        dropdown.classList.toggle('active');
      };

      document.addEventListener('click', () => dropdown.classList.remove('active'));
      dropdown.onclick = (e) => e.stopPropagation();

      updateDisplay();
    }

    initTimePicker('openingTimePicker', '08:00 am');
    initTimePicker('closingTimePicker', '09:00 pm');

    document.getElementById('scTabs').addEventListener('click', function (e) {
      const btn = e.target.closest('.sc-tab');
      if (!btn) return;
      document.querySelectorAll('.sc-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.sc-tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const panel = document.getElementById('tab-' + btn.dataset.tab);
      if (panel) panel.classList.add('active');
    });

    // Payment type pills
    function setActivePaymentPill(group, activeButton) {
      group.querySelectorAll('.sc-payment-pill').forEach(btn => {
        const isActive = btn === activeButton;
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
      const selectedLabel = activeButton.textContent.trim();
      const card = group.closest('.sc-payment-card');
      if (!card) return;
      const dp = card.querySelector('#defaultPaymentType');
      const drp = card.querySelector('#defaultPurchasesPaymentType');
      if (dp && dp.options.length > 0) dp.options[0].textContent = selectedLabel;
      if (drp && drp.options.length > 0) drp.options[0].textContent = selectedLabel;
    }

    document.querySelectorAll('.sc-payment-pills').forEach(group => {
      const buttons = group.querySelectorAll('.sc-payment-pill');
      if (!buttons.length) return;
      buttons.forEach(button => {
        button.addEventListener('click', function () { setActivePaymentPill(group, this); });
      });
      setActivePaymentPill(group, buttons[0]);
    });

    // Add Shipping Provider row
    function addShippingProvider() {
      const tbody = document.getElementById('shippingProvidersBody');
      if (!tbody) return;
      const tr = document.createElement('tr');
      tr.innerHTML = `
      <td><input type="text" style="width:90px;height:26px;border:1px solid #d1d5db;border-radius:2px;padding:2px 6px;font-size:12px;" /></td>
      <td><input type="text" style="width:90px;height:26px;border:1px solid #d1d5db;border-radius:2px;padding:2px 6px;font-size:12px;" /></td>
      <td><input type="text" style="width:70px;height:26px;border:1px solid #d1d5db;border-radius:2px;padding:2px 6px;font-size:12px;" /></td>
      <td><input type="text" style="width:70px;height:26px;border:1px solid #d1d5db;border-radius:2px;padding:2px 6px;font-size:12px;" /></td>
      <td style="text-align:center;"><input type="radio" name="shippingProviderDefault" style="accent-color:var(--primary);" /></td>
      <td style="text-align:center;"><button onclick="this.closest('tr').remove()" style="background:none;border:none;color:#ef4444;font-size:12px;font-weight:600;cursor:pointer;">Delete</button></td>
      <td style="text-align:center;"><button style="background:none;border:none;color:var(--primary);font-size:12px;font-weight:600;cursor:pointer;">Add Rate</button></td>
      <td style="text-align:center;"><button style="background:none;border:none;font-size:15px;cursor:pointer;color:#64748b;">⇅</button></td>
    `;
      tbody.appendChild(tr);
    }

    // Reusable save feedback
    function attachSaveFeedback(id) {
      const btn = document.getElementById(id);
      if (!btn) return;
      btn.addEventListener('click', function () {
        this.textContent = 'Saved!';
        this.style.background = '#16a34a';
        setTimeout(() => { this.textContent = 'Save'; this.style.background = ''; }, 1800);
      });
    }

    attachSaveFeedback('btnSaveCompany');
    attachSaveFeedback('btnSaveTaxes');
    attachSaveFeedback('btnSaveCurrency');
    attachSaveFeedback('btnSavePaymentTypes');
    attachSaveFeedback('btnSavePriceRules');
    attachSaveFeedback('btnSaveOrdersDeliveries');
    attachSaveFeedback('btnSaveSales');
    attachSaveFeedback('btnSaveProfit');
    attachSaveFeedback('btnSaveLoyalty');
    attachSaveFeedback('btnSavePriceTiers');
    attachSaveFeedback('btnSaveIdNumbers');
    attachSaveFeedback('btnSaveDisableModules');
    attachSaveFeedback('btnSaveAppSettings');
    attachSaveFeedback('btnSaveEmailSettings');
    attachSaveFeedback('btnSaveQuickbooks');
    attachSaveFeedback('btnSaveEcommerce');
    attachSaveFeedback('btnSaveApiSettings');
    attachSaveFeedback('btnSaveWebHooks');
    attachSaveFeedback('btnSaveLookupApi');

    // API Key Modal Logic
    const btnConfirmAddApiKey = document.getElementById('btnConfirmAddApiKey');
    if (btnConfirmAddApiKey) {
      btnConfirmAddApiKey.addEventListener('click', function () {
        const desc = document.getElementById('apiKeyDesc').value;
        const key = document.getElementById('generatedApiKey').value;
        const perms = document.getElementById('apiKeyPerms').value;

        if (!desc) {
          alert('Please enter a description');
          return;
        }

        const tbody = document.getElementById('apiKeysBody');
        const lastFour = key.substring(key.length - 4);
        const row = document.createElement('tr');
        row.innerHTML = `
          <td style="padding: 12px 0;">${desc}</td>
          <td style="padding: 12px 0;">**********${lastFour}</td>
          <td style="padding: 12px 0;">${perms}</td>
          <td style="padding: 12px 0; text-align: right;">
            <button class="btn btn-link p-0 text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button>
          </td>
        `;
        tbody.appendChild(row);

        // Reset and Close
        document.getElementById('apiKeyDesc').value = '';
        const modal = bootstrap.Modal.getInstance(document.getElementById('addApiKeyModal'));
        modal.hide();
      });
    }

    // Sync Operation Toggles
    document.addEventListener('click', function (e) {
      const op = e.target.closest('.sc-sync-op');
      if (op) {
        op.classList.toggle('inactive');
      }
    });

    // Price Tiers table logic
    function addPriceTierRow() {
      const tbody = document.getElementById('priceTiersBody');
      if (!tbody) return;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="text-align: center; padding: 14px 10px;"><button style="background:none;border:none;font-size:16px;cursor:pointer;color:#374151;padding:0;font-weight:bold;">⇅</button></td>
        <td style="padding: 14px 10px;"><input type="text" class="sc-profit-input" value="" style="width:100%; max-width: none;" /></td>
        <td style="padding: 14px 10px;"><input type="text" class="sc-profit-input" value="" style="width:100%; max-width: none;" /></td>
        <td style="padding: 14px 10px;"><input type="text" class="sc-profit-input" value="" style="width:100%; max-width: none;" /></td>
        <td style="padding: 14px 10px;"><input type="text" class="sc-profit-input" value="" style="width:100%; max-width: none;" /></td>
        <td style="text-align: center; padding: 14px 10px;"><button onclick="this.closest('tr').remove()" style="background:none;border:none;color:#ef4444;font-size:13px;cursor:pointer;padding:0;">Delete</button></td>
      `;
      tbody.appendChild(tr);
    }

    // Initialize with one row
    if (document.getElementById('priceTiersBody')) {
      addPriceTierRow();
    }

    // Link "Manage Price Tiers" to the tab
    document.addEventListener('click', function(e) {
      if (e.target.tagName === 'A' && e.target.textContent.includes('Manage Price Tiers')) {
        e.preventDefault();
        const tabBtn = document.querySelector('.sc-tab[data-tab="price-tiers"]');
        if (tabBtn) tabBtn.click();
      }
    });




