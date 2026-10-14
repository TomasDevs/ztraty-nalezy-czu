import '../css/main.css';

// Modules are loaded lazily, only on pages that contain their element.
const modules = [
  ['[data-map]', () => import('./modules/map.js')],
  ['[data-items-filter]', () => import('./modules/items-filter.js')],
];

modules.forEach(([selector, load]) => {
  const elements = document.querySelectorAll(selector);

  if (elements.length) {
    load().then(({ default: init }) => elements.forEach((el) => init(el)));
  }
});
