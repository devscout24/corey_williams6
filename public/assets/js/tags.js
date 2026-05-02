const tagModal = document.getElementById('tagModal');
  const tagInput = tagModal.querySelector('.tag-input-custom');
  const tagTitle = tagModal.querySelector('.modal-title-tag');

  tagModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const tagName = button.getAttribute('data-tag-name');

    if (tagName) {
      // It's an Edit action
      tagInput.value = tagName;
      tagTitle.textContent = 'Edit tag name';
    } else {
      // It's an Add action
      tagInput.value = '';
      tagTitle.textContent = 'Please enter tag name';
    }
  });




