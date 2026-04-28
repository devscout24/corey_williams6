document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('eyeBtn');
  if (!toggleBtn) return;

  toggleBtn.addEventListener('click', () => {
    const input = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (!input || !icon) return;

    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'bi bi-eye' : 'bi bi-eye-slash';
  });
});
