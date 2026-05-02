// Tab toggle logic
  const btnBarcode = document.getElementById('btnBarcode');
  const btnShelf = document.getElementById('btnShelf');
  const barcodeContent = document.getElementById('barcodeContent');
  const shelfContent = document.getElementById('shelfContent');
  const btnAddLogo = document.getElementById('btnAddLogo');
  const labelsCard = document.querySelector('.labels-card');

  btnBarcode.addEventListener('click', () => {
    btnBarcode.classList.add('active');
    btnShelf.classList.remove('active');
    barcodeContent.style.display = 'block';
    shelfContent.style.display = 'none';
    btnAddLogo.style.display = 'none';
    labelsCard.style.maxWidth = '100%';
  });

  btnShelf.addEventListener('click', () => {
    btnShelf.classList.add('active');
    btnBarcode.classList.remove('active');
    barcodeContent.style.display = 'none';
    shelfContent.style.display = 'block';
    btnAddLogo.style.display = 'block';
    labelsCard.style.maxWidth = '100%';
  });

  // Logo upload logic
  const logoInput = document.getElementById('logoInput');
  const logoPreview = document.getElementById('logoPreview');

  // Load saved logo from localStorage
  const savedLogo = localStorage.getItem('labelBackgroundLogo');
  if (savedLogo) {
    logoPreview.src = savedLogo;
  }

  btnAddLogo.addEventListener('click', () => {
    logoInput.click();
  });

  logoInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (event) => {
        const dataUrl = event.target.result;
        logoPreview.src = dataUrl;
        localStorage.setItem('labelBackgroundLogo', dataUrl);
      };
      reader.readAsDataURL(file);
    }
  });



