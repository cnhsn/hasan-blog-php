(() => {
  document.addEventListener('click', (event) => {
    const button = event.target.closest('.code-copy-button');
    if (!button) return;
    const code = button.closest('.code-block')?.querySelector('code');
    if (!code) return;
    navigator.clipboard?.writeText(code.textContent || '').then(() => {
      const original = button.textContent;
      button.textContent = 'Copied!';
      setTimeout(() => { button.textContent = original; }, 2000);
    }).catch(() => {});
  });
})();
