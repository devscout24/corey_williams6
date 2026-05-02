function showReportView() {
    // Hide all open modals
    ['existingVatModal', 'addVatModal'].forEach(function(id) {
      const el = document.getElementById(id);
      if (el) {
        const inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
      }
    });

    // Remove any leftover modal backdrops and body classes
    function doShow() {
      document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';

      const tableSection = document.getElementById('tableViewSection');
      const reportSection = document.getElementById('reportViewSection');
      if (tableSection && reportSection) {
        tableSection.style.setProperty('display', 'none', 'important');
        reportSection.style.setProperty('display', 'block', 'important');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }

    // Small delay to let Bootstrap finish its hide animation
    setTimeout(doShow, 300);
  }

  function showTableView() {
    const tableSection = document.getElementById('tableViewSection');
    const reportSection = document.getElementById('reportViewSection');
    if (tableSection && reportSection) {
      tableSection.style.display = 'block';
      reportSection.style.display = 'none';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  // Use Bootstrap event listener to update the month text dynamically
  document.addEventListener('DOMContentLoaded', function() {
    const existingModalEl = document.getElementById('existingVatModal');
    if (existingModalEl) {
      existingModalEl.addEventListener('show.bs.modal', function() {
        const monthInput = document.querySelector('#addVatModal .month-picker-input');
        const displaySpan = document.getElementById('existingVatMonth');
        
        if (monthInput && displaySpan && monthInput.value) {
          const [year, month] = monthInput.value.split('-');
          if (year && month) {
            const dateObj = new Date(year, month - 1);
            const formatted = dateObj.toLocaleString('default', { month: 'long', year: 'numeric' });
            displaySpan.innerText = formatted;
          }
        }
      });
    }
  });

  function printVatReturn() {
    const printArea = document.getElementById('vatPrintArea');
    if (!printArea) {
      alert("Print area not found!");
      return;
    }
    
    // Synchronize input values to 'value' attributes so they appear in print
    const inputs = printArea.querySelectorAll('input');
    inputs.forEach(input => {
      input.setAttribute('value', input.value);
    });

    // Use a hidden iframe for more robust printing (avoids popup blockers)
    let iframe = document.getElementById('vatPrintIframe');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'vatPrintIframe';
      iframe.style.position = 'absolute';
      iframe.style.top = '-10000px';
      document.body.appendChild(iframe);
    }

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(`
      <html>
        <head>
          <title>VAT Return - Print</title>
          <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
          <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
          <link rel="stylesheet" href="css/vat-report.css">
        </head>
        <body onload="setTimeout(() => { window.print(); }, 800)">
          <div class="container">
            ${printArea.innerHTML}
          </div>
        </body>
      </html>
    `);
    doc.close();
  }




