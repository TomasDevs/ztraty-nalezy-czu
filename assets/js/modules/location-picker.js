import L from 'leaflet';
import { createMap, pinIcon } from './map-core.js';

const BUILDING_ZOOM = 17;

/**
 * Progressive enhancement: without JS the server falls back to the building's coordinates.
 */
export default function initLocationPicker(el) {
  const form = el.closest('form');
  const { lat, lng, building } = form.elements;
  const reset = form.querySelector('[data-location-reset]');
  const buildings = JSON.parse(el.dataset.buildings || '[]');
  const map = createMap(el);
  let marker = null;

  const hasPlace = () => lat.value !== '' && lng.value !== '';

  const place = (latlng) => {
    if (marker) marker.setLatLng(latlng);
    else marker = L.marker(latlng, { icon: pinIcon(el.dataset.type || 'found'), keyboard: false }).addTo(map);
    reset.hidden = false;
  };

  const showBuilding = () => {
    const selected = buildings.find(({ slug }) => slug === building.value);
    if (selected && !hasPlace()) map.setView([selected.lat, selected.lng], BUILDING_ZOOM);
  };

  map.on('click', ({ latlng }) => {
    lat.value = latlng.lat.toFixed(6);
    lng.value = latlng.lng.toFixed(6);
    place(latlng);
  });

  reset.addEventListener('click', () => {
    lat.value = '';
    lng.value = '';
    marker?.remove();
    marker = null;
    reset.hidden = true;
    showBuilding();
  });

  building.addEventListener('change', showBuilding);

  if (hasPlace()) {
    place([Number(lat.value), Number(lng.value)]);
    map.setView(marker.getLatLng(), BUILDING_ZOOM);
  } else {
    showBuilding();
  }
}
