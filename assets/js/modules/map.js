import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import { createMap, pinIcon } from './map-core.js';

// Nearby pins merge into one, so overlapping markers stay clickable.
const clusterIcon = (cluster) =>
  L.divIcon({
    className: 'cluster',
    html: `<span aria-hidden="true">${cluster.getChildCount()}</span><span class="visually-hidden">Přiblížit skupinu (${cluster.getChildCount()})</span>`,
    iconSize: [32, 32],
  });

const popupLink = ({ title, url }) => {
  const link = document.createElement('a');
  link.href = url;
  link.textContent = title;
  return link;
};

export default function initMap(el) {
  const map = createMap(el);
  const layer = L.markerClusterGroup({
    iconCreateFunction: clusterIcon,
    maxClusterRadius: 36,
    showCoverageOnHover: false,
  }).addTo(map);

  const render = (markers) => {
    layer.clearLayers();

    markers.forEach((marker) => {
      const pin = L.marker([marker.lat, marker.lng], {
        icon: pinIcon(marker.type),
        title: marker.title,
        interactive: Boolean(marker.url),
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

  map.on('popupopen', ({ popup }) => {
    popup.getElement().querySelector('.leaflet-popup-close-button')?.setAttribute('aria-label', 'Zavřít');
  });
}
