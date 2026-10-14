import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

const CAMPUS_CENTER = [50.13, 14.3745];

// Leaflet resolves icon paths at runtime, which breaks once assets are bundled.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow });

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
      const pin = L.marker([marker.lat, marker.lng], { title: marker.title, alt: marker.title }).addTo(layer);
      if (marker.url) pin.bindPopup(popupLink(marker));
    });

    if (markers.length) {
      map.fitBounds(markers.map(({ lat, lng }) => [lat, lng]), { maxZoom: 17, padding: [24, 24] });
    }
  };

  render(JSON.parse(el.dataset.markers || '[]'));
  el.addEventListener('markers:update', (event) => render(event.detail));
}
