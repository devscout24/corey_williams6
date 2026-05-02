// File upload trigger logic
  document.querySelectorAll('.btn-choose-trigger').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const parent = e.target.closest('.input-group-custom');
      const input = parent.querySelector('.category-image-input');
      input.click();
    });
  });

  // Handle file selection
  document.querySelectorAll('.category-image-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const parent = e.target.closest('.modal-body');
        const pathInput = parent.querySelector('.category-image-path');
        const previewImg = parent.querySelector('.edit-image-preview');
        
        pathInput.value = file.name;

        // If it's the edit modal (or any modal with a preview img), update the preview
        if (previewImg) {
          const reader = new FileReader();
          reader.onload = (event) => {
            previewImg.src = event.target.result;
          };
          reader.readAsDataURL(file);
        }
      }
    });
  });




