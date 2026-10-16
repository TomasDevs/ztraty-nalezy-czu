import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export const CAMPUS_CENTER = [50.13, 14.3745];

// 24px hit area meets the WCAG 2.2 target size, the visible dot is smaller.
export const pinIcon = (type) =>
  L.divIcon({
    className: `pin pin--${type}`,
    iconSize: [24, 24],
    iconAnchor: [12, 12],
    popupAnchor: [0, -12],
  });

export function createMap(el, zoom = 16) {
  const map = L.map(el, { maxZoom: 19, scrollWheelZoom: false, zoomControl: false }).setView(CAMPUS_CENTER, zoom);
  L.control.zoom({ zoomInTitle: 'Přiblížit', zoomOutTitle: 'Oddálit' }).addTo(map);

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    detectRetina: true,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  return map;
}
