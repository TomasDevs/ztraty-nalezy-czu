import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const CAMPUS_CENTER = [50.13, 14.3745];

const pinIcon = (type) =>
  L.divIcon({
    className: `pin pin--${type}`,
    iconSize: [18, 18],
    iconAnchor: [9, 9],
    popupAnchor: [0, -10],
  });

const popupLink = ({ title, url }) => {
  const link = document.createElement('a');
  link.href = url;
  link.textContent = title;
  return link;
};

export default function initMap(el) {
  const map = L.map(el, { scrollWheelZoom: false }).setView(CAMPUS_CENTER, 16);
  const layer = L.layerGroup().addTo(map);

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  const render = (markers) => {
    layer.clearLayers();

    markers.forEach((marker) => {
      const pin = L.marker([marker.lat, marker.lng], {
        icon: pinIcon(marker.type),
        title: marker.title,
        alt: marker.title,
        keyboard: Boolean(marker.url),
      }).addTo(layer);

      if (marker.url) pin.bindPopup(popupLink(marker));
    });

    if (markers.length) {
      map.fitBounds(markers.map(({ lat, lng }) => [lat, lng]), { maxZoom: 17, padding: [32, 32] });
    }
  };

  render(JSON.parse(el.dataset.markers || '[]'));
  el.addEventListener('markers:update', (event) => render(event.detail));
}
