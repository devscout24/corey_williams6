const attrModal = document.getElementById('attrModal');
  const attrNameInput = document.getElementById('attrNameInput');
  const attrValuesInput = document.getElementById('attrValuesInput');
  const attrModalTitle = document.getElementById('attrModalTitle');

  attrModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const attrName = button.getAttribute('data-attr-name');
    const attrValues = button.getAttribute('data-attr-values');

    if (attrName) {
      attrModalTitle.textContent = 'Edit Attribute';
      attrNameInput.value = attrName;
      attrValuesInput.value = attrValues === 'Not set' ? '' : attrValues;
    } else {
      attrModalTitle.textContent = 'Add Attribute';
      attrNameInput.value = '';
      attrValuesInput.value = '';
    }
  });




