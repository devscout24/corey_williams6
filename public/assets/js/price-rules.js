const editRuleModal = document.getElementById('editRuleModal');
  editRuleModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const ruleName = button.getAttribute('data-rule-name');
    const ruleType = button.getAttribute('data-rule-type');

    document.getElementById('editRuleName').value = ruleName;
    document.getElementById('editRuleType').value = ruleType;
  });




