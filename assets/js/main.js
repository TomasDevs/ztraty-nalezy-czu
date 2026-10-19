import '../css/main.css';

// Modules are loaded lazily, only on pages that contain their element.
const modules = [
  ['[data-map]', () => import('./modules/map.js')],
  ['[data-items-filter]', () => import('./modules/items-filter.js')],
  ['[data-share]', () => import('./modules/share.js')],
  ['[data-location-picker]', () => import('./modules/location-picker.js')],
  ['form[data-confirm]', () => import('./modules/confirm.js')],
  ['[data-menu-toggle]', () => import('./modules/menu.js')],
];

modules.forEach(([selector, load]) => {
  const elements = document.querySelectorAll(selector);

  if (elements.length) {
    load().then(({ default: init }) => elements.forEach((el) => init(el)));
  }
});
