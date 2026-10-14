const RESET_MS = 2500;

/**
 * Native share sheet on mobile, copy-to-clipboard fallback on desktop.
 */
export default function initShare(button) {
  const status = button.parentElement.querySelector('[data-share-status]');
  const label = button.textContent.trim();
  const { url, title } = button.dataset;

  if (!navigator.share && !navigator.clipboard) return;
  button.hidden = false;

  button.addEventListener('click', async () => {
    if (navigator.share) {
      try {
        await navigator.share({ title, url });
      } catch {
        // dismissed by the user
      }
      return;
    }

    await navigator.clipboard.writeText(url);
    button.textContent = 'Odkaz zkopírován';
    if (status) status.textContent = 'Odkaz na inzerát je zkopírovaný ve schránce.';

    setTimeout(() => {
      button.textContent = label;
      if (status) status.textContent = '';
    }, RESET_MS);
  });
}
