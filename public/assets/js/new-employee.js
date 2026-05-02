function updateColorSwatch(color) {
    document.getElementById('colorSwatch').style.backgroundColor = color;
  }

  function previewAvatar(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const preview = document.getElementById('avatarPreview');
        preview.style.backgroundImage = `url(${e.target.result})`;
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
        preview.innerHTML = ''; // Clear icon and text
        preview.classList.add('has-image');
      }
      reader.readAsDataURL(input.files[0]);
    }
  }




