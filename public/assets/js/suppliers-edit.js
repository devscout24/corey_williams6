function previewAvatar(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      const fileName = input.files[0].name;
      document.getElementById('avatarName').value = fileName;
      
      reader.onload = function(e) {
        const preview = document.getElementById('avatarPreview');
        preview.style.backgroundImage = `url(${e.target.result})`;
        preview.classList.add('has-image');
      }
      reader.readAsDataURL(input.files[0]);
    }
  }




