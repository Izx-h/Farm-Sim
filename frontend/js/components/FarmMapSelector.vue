<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import { api } from '../api';

const emit = defineEmits(['lookup']);
const props = defineProps({
  latitude: { type: Number, default: -0.3031 },
  longitude: { type: Number, default: 36.08 },
  zoom: { type: Number, default: 7 },
});

const mapEl = ref(null);
const marker = ref(null);
const picking = ref(false);

let map = null;

function initMap() {
  if (!mapEl.value) return;
  map = L.map(mapEl.value).setView([props.latitude, props.longitude], props.zoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  marker.value = L.circleMarker([props.latitude, props.longitude], {
    radius: 8,
    color: '#10b981',
    fillColor: '#34d399',
    fillOpacity: 0.9,
  }).addTo(map);

  map.on('click', (e) => {
    const { lat, lng } = e.latlng;
    marker.value.setLatLng([lat, lng]);
    picking.value = true;
    emit('lookup', { lat, lng });
  });
}

function setView(lat, lng) {
  if (!map) return;
  map.setView([lat, lng], map.getZoom());
  marker.value.setLatLng([lat, lng]);
}

watch(
  () => [props.latitude, props.longitude],
  ([lat, lng]) => {
    if (map) setView(lat, lng);
  }
);

onMounted(initMap);
onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

defineExpose({ setView, setPicking: (v) => (picking.value = v) });
</script>

<template>
  <div class="map-container" ref="mapEl"></div>
  <div v-if="picking" class="map-loading-badge" style="position: relative; margin-top: 0.5rem; text-align: center">
    📡 Resolving location… click the map to drop a pin
  </div>
</template>
