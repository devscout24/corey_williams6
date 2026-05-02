function switchTab(el, tabId) {
    // If not on Info tab and trying to switch, or if on Info tab and trying to switch away
    // We check validation first
    const nameInput = document.querySelector('input[placeholder=""]');
    const categorySelect = document.querySelector('select.form-input-kit');
    
    if (tabId !== 'tabInfo') {
      if (!nameInput.value.trim() || categorySelect.value === 'Select Category') {
        // Show modal instead of switching
        const vModal = new bootstrap.Modal(document.getElementById('validationModal'));
        vModal.show();
        
        // Also trigger the red marks for visual feedback
        validateForm(true); 
        return;
      }
    }

    // Update active tab link
    document.querySelectorAll('.kit-tab').forEach(tab => tab.classList.remove('active'));
    el.classList.add('active');

    // Show corresponding content
    document.querySelectorAll('.kit-tab-content').forEach(content => content.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';
  }

  function validateForm(isSilent = false) {
    const nameInput = document.querySelector('input[placeholder=""]');
    const categorySelect = document.querySelector('select.form-input-kit');
    let hasError = false;

    // Reset errors
    document.querySelectorAll('.label-required').forEach(l => l.classList.remove('error'));
    document.querySelectorAll('.validation-msg').forEach(m => m.style.display = 'none');

    if (!nameInput.value.trim()) {
      nameInput.closest('.form-row-kit').querySelector('.label-required').classList.add('error');
      nameInput.closest('.form-row-kit').nextElementSibling.style.display = 'flex';
      hasError = true;
    }

    if (categorySelect.value === 'Select Category') {
      categorySelect.closest('.form-row-kit').querySelector('.label-required').classList.add('error');
      categorySelect.closest('.form-row-kit').nextElementSibling.style.display = 'flex';
      hasError = true;
    }

    if (!hasError && !isSilent) {
      alert('Form saved successfully!');
    }
  }




