document.addEventListener('DOMContentLoaded', () => {
    const savedLogo = localStorage.getItem('labelBackgroundLogo');
    if (savedLogo) {
      const logoImages = document.querySelectorAll('.label-bg-logo');
      logoImages.forEach(img => {
        img.src = savedLogo;
      });
    }
  });




