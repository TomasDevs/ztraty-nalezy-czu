const DEBOUNCE_MS = 300;

const debounce = (fn, ms) => {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
};

/**
 * Progressive enhancement: the form and "load more" link work without JS,
 * this module swaps them for AJAX requests to /api/items.
 */
export default function initItemsFilter(root) {
  const form = root.querySelector('form');
  const list = root.querySelector('[data-items-list]');
  const count = root.querySelector('[data-items-count]');
  const empty = root.querySelector('[data-items-empty]');
  const more = root.querySelector('[data-load-more]');
  const map = root.querySelector('[data-map]');
  let request;

  const buildQuery = (page) => {
    const query = new URLSearchParams();
    new FormData(form).forEach((value, key) => value && query.set(key, value));
    if (page > 1) query.set('page', page);
    return query;
  };

  const withQuery = (base, query) => (query.toString() ? `${base}?${query}` : base);
  const pageUrl = (query) => withQuery(form.action, query);

  async function load(page = 1) {
    request?.abort();
    request = new AbortController();

    const query = buildQuery(page);
    const previousCount = list.children.length;
    root.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(withQuery(form.dataset.api, query), {
        signal: request.signal,
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();

      if (page === 1) {
        list.innerHTML = data.html;
        history.replaceState(null, '', pageUrl(query));
        map?.dispatchEvent(new CustomEvent('markers:update', { detail: data.markers }));
      } else {
        list.insertAdjacentHTML('beforeend', data.html);
        list.children[previousCount]?.querySelector('a')?.focus();
      }

      count.textContent = `Nalezeno: ${data.total}`;
      empty.hidden = data.total > 0;
      more.hidden = !data.nextPage;

      if (data.nextPage) {
        more.dataset.page = data.nextPage;
        more.href = pageUrl(buildQuery(data.nextPage));
      }
    } catch (error) {
      if (error.name !== 'AbortError') window.location.assign(pageUrl(query));
    } finally {
      root.removeAttribute('aria-busy');
    }
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    load();
  });
  form.addEventListener('change', () => load());
  form.elements.q?.addEventListener('input', debounce(() => load(), DEBOUNCE_MS));

  more.addEventListener('click', (event) => {
    event.preventDefault();
    load(Number(more.dataset.page));
  });
}
