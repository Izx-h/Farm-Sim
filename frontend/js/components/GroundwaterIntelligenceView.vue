<script setup>
import { ref, onMounted, computed } from 'vue';
import { api } from '../api';

const props = defineProps({
  farm: { type: Object, required: true },
  crops: { type: Array, default: () => [] },
});

const emit = defineEmits(['select-farm']);
const stations = ref([]);
const filter = ref('');

const gw = computed(() => ({
  aquifer_name: props.farm.aquifer_basin || 'Local Aquifer Basin',
  depth: props.farm.groundwater_depth_m,
  yield: props.farm.groundwater_yield_m3_hr,
  ec: props.farm.groundwater_ec_ds_m,
  tds: props.farm.groundwater_tds_mg_l,
  salinity: props.farm.groundwater_salinity_class || 'Fresh',
  suitability: props.farm.groundwater_irrigation_suitability || 'Class 1 - Highly Suitable',
}));

const badge = computed(() => {
  const s = gw.value.salinity.toLowerCase();
  if (s.includes('saline') || s.includes('brackish')) return 'rose';
  if (s.includes('mineral') || s.includes('slightly')) return 'amber';
  if (s.includes('fresh to mildly')) return 'blue';
  return 'emerald';
});

onMounted(async () => {
  try {
    stations.value = await api.getGroundwaterStations();
  } catch (err) {
    console.error(err);
  }
});
</script>

<template>
  <div>
    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">💧 Groundwater intelligence</div></div>
        <span class="tag" :class="`tag-${badge}`">{{ gw.salinity }}</span>
      </div>

      <div class="grid-3">
        <div class="overview-pill"><div class="overview-icon-box blue">🕳️</div><div><div class="small muted">Water table depth</div><strong>{{ gw.depth ?? '—' }} m</strong></div></div>
        <div class="overview-pill"><div class="overview-icon-box emerald">🚿</div><div><div class="small muted">Sustainable yield</div><strong>{{ gw.yield ?? '—' }} m³/hr</strong></div></div>
        <div class="overview-pill"><div class="overview-icon-box amber">🧂</div><div><div class="small muted">EC / TDS</div><strong>{{ gw.ec ?? '—' }} dS/m</strong><div class="small secondary">{{ gw.tds ?? '—' }} mg/L</div></div></div>
      </div>

      <p class="small secondary" style="margin-top: 1rem">
        🏞️ Aquifer: <strong>{{ gw.aquifer_name }}</strong> · {{ gw.suitability }}
      </p>
      <div class="data-classification-banner">ℹ️ Groundwater values are <em>estimated</em> from the nearest reference station unless a laboratory water test has been recorded for this farm.</div>
    </div>

    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">🇰🇪 National groundwater station registry</div></div>
        <input v-model="filter" class="form-input" style="max-width: 220px" placeholder="Filter by county or basin" />
      </div>
      <table class="data-table">
        <thead><tr><th>Station</th><th>County</th><th>Aquifer</th><th>EC</th><th>TDS</th><th>Salinity</th></tr></thead>
        <tbody>
          <tr v-for="s in stations.filter((x) => !filter || `${x.county} ${x.basin_name}`.toLowerCase().includes(filter.toLowerCase()))" :key="s.id">
            <td>{{ s.name }}</td>
            <td>{{ s.county }}</td>
            <td>{{ s.aquifer_name }}</td>
            <td>{{ s.ec_ds_m }}</td>
            <td>{{ s.tds_mg_l }}</td>
            <td><span class="tag tag-blue">{{ s.salinity_category }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
