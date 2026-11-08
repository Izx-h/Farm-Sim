<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { api } from '../api';

const props = defineProps({
  farm: { type: Object, required: true },
  farms: { type: Array, default: () => [] },
});

const emit = defineEmits(['select-farm', 'refresh-farm', 'add-new-farm', 'add-soil-test', 'add-water-test', 'update-farm']);

const detail = ref(null);
const showSoil = ref(false);
const showWater = ref(false);
const showEdit = ref(false);

const soilForm = reactive({ ph: '', n_ppm: '', p_ppm: '', k_ppm: '', laboratory: '' });
const waterForm = reactive({ ph: '', ec_ds_m: '', tds_mg_l: '', fluoride_mg_l: '', laboratory: '' });
const editForm = reactive({});

async function load() {
  try {
    detail.value = await api.getFarm(props.farm.id);
    Object.assign(editForm, props.farm);
  } catch (err) {
    console.error(err);
  }
}

onMounted(load);
watch(() => props.farm?.id, load);

async function submitSoil() {
  await emit('add-soil-test', props.farm.id, { ...soilForm });
  showSoil.value = false;
  await load();
}
async function submitWater() {
  await emit('add-water-test', props.farm.id, { ...waterForm });
  showWater.value = false;
  await load();
}
async function submitEdit() {
  await emit('update-farm', props.farm.id, { ...editForm });
  showEdit.value = false;
  await load();
}

const gw = detail;
</script>

<template>
  <div>
    <div class="glass-card">
      <div class="card-header-clean">
        <div>
          <div class="card-title">🏡 {{ farm.name }}</div>
          <div class="card-subtitle">{{ farm.location_name }} · {{ farm.area_acres }} acres · {{ farm.farming_goal }}</div>
        </div>
        <div>
          <button class="btn-secondary btn-sm" @click="showEdit = true">✏️ Edit</button>
        </div>
      </div>

      <div class="grid-3">
        <div class="overview-pill"><div class="overview-icon-box emerald">🧪</div><div><div class="small muted">Soil</div><strong>{{ farm.soil_type }}</strong><div class="small secondary">pH {{ farm.soil_ph }} · {{ farm.drainage_class }}</div></div></div>
        <div class="overview-pill"><div class="overview-icon-box blue">⛰️</div><div><div class="small muted">Terrain</div><strong>{{ farm.elevation_m }}m</strong><div class="small secondary">{{ farm.slope_deg }}° slope</div></div></div>
        <div class="overview-pill"><div class="overview-icon-box amber">💧</div><div><div class="small muted">Water</div><strong>{{ farm.water_source }}</strong><div class="small secondary">{{ farm.groundwater_salinity_class || 'Fresh' }}</div></div></div>
        <div class="overview-pill"><div class="overview-icon-box emerald">🌱</div><div><div class="small muted">Nitrogen</div><strong>{{ farm.nitrogen_level }}</strong></div></div>
        <div class="overview-pill"><div class="overview-icon-box purple">🧪</div><div><div class="small muted">Phosphorus</div><strong>{{ farm.phosphorus_level }}</strong></div></div>
        <div class="overview-pill"><div class="overview-icon-box blue">🧪</div><div><div class="small muted">Potassium</div><strong>{{ farm.potassium_level }}</strong></div></div>
      </div>
    </div>

    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">🧪 Soil laboratory tests</div></div>
        <button class="btn-primary btn-sm" @click="showSoil = true">➕ Add soil test</button>
      </div>
      <p v-if="!detail?.soilTests?.length" class="small secondary">No laboratory soil tests recorded. Add one for a precise fertilizer prescription.</p>
      <table v-else class="data-table">
        <thead><tr><th>Date</th><th>Lab</th><th>pH</th><th>N</th><th>P</th><th>K</th></tr></thead>
        <tbody>
          <tr v-for="t in detail.soilTests" :key="t.id">
            <td>{{ t.test_date }}</td><td>{{ t.laboratory }}</td>
            <td>{{ t.ph ?? '—' }}</td><td>{{ t.n_ppm ?? '—' }}</td><td>{{ t.p_ppm ?? '—' }}</td><td>{{ t.k_ppm ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">💧 Water laboratory tests</div></div>
        <button class="btn-primary btn-sm" @click="showWater = true">➕ Add water test</button>
      </div>
      <p v-if="!detail?.waterTests?.length" class="small secondary">No laboratory water tests recorded.</p>
      <table v-else class="data-table">
        <thead><tr><th>Date</th><th>Lab</th><th>pH</th><th>EC</th><th>TDS</th><th>Fluoride</th></tr></thead>
        <tbody>
          <tr v-for="t in detail.waterTests" :key="t.id">
            <td>{{ t.test_date }}</td><td>{{ t.laboratory }}</td>
            <td>{{ t.ph ?? '—' }}</td><td>{{ t.ec_ds_m ?? '—' }}</td><td>{{ t.tds_mg_l ?? '—' }}</td><td>{{ t.fluoride_mg_l ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Soil test modal -->
    <div v-if="showSoil" class="modal-overlay" @click.self="showSoil = false">
      <div class="modal-content">
        <h3>Add soil test</h3>
        <div class="form-grid-2">
          <div class="form-group"><label class="form-label">pH</label><input v-model="soilForm.ph" class="form-input" type="number" step="0.1" /></div>
          <div class="form-group"><label class="form-label">N (ppm)</label><input v-model="soilForm.n_ppm" class="form-input" type="number" /></div>
          <div class="form-group"><label class="form-label">P (ppm)</label><input v-model="soilForm.p_ppm" class="form-input" type="number" /></div>
          <div class="form-group"><label class="form-label">K (ppm)</label><input v-model="soilForm.k_ppm" class="form-input" type="number" /></div>
          <div class="form-group"><label class="form-label">Laboratory</label><input v-model="soilForm.laboratory" class="form-input" /></div>
        </div>
        <div class="flex-between"><button class="btn-secondary" @click="showSoil = false">Cancel</button><button class="btn-primary" @click="submitSoil">Save</button></div>
      </div>
    </div>

    <!-- Water test modal -->
    <div v-if="showWater" class="modal-overlay" @click.self="showWater = false">
      <div class="modal-content">
        <h3>Add water test</h3>
        <div class="form-grid-2">
          <div class="form-group"><label class="form-label">pH</label><input v-model="waterForm.ph" class="form-input" type="number" step="0.1" /></div>
          <div class="form-group"><label class="form-label">EC (dS/m)</label><input v-model="waterForm.ec_ds_m" class="form-input" type="number" step="0.01" /></div>
          <div class="form-group"><label class="form-label">TDS (mg/L)</label><input v-model="waterForm.tds_mg_l" class="form-input" type="number" /></div>
          <div class="form-group"><label class="form-label">Fluoride (mg/L)</label><input v-model="waterForm.fluoride_mg_l" class="form-input" type="number" step="0.1" /></div>
          <div class="form-group"><label class="form-label">Laboratory</label><input v-model="waterForm.laboratory" class="form-input" /></div>
        </div>
        <div class="flex-between"><button class="btn-secondary" @click="showWater = false">Cancel</button><button class="btn-primary" @click="submitWater">Save</button></div>
      </div>
    </div>

    <!-- Edit modal -->
    <div v-if="showEdit" class="modal-overlay" @click.self="showEdit = false">
      <div class="modal-content">
        <h3>Edit farm</h3>
        <div class="form-grid-2">
          <div class="form-group"><label class="form-label">Name</label><input v-model="editForm.name" class="form-input" /></div>
          <div class="form-group"><label class="form-label">Owner</label><input v-model="editForm.owner" class="form-input" /></div>
          <div class="form-group"><label class="form-label">Area (acres)</label><input v-model.number="editForm.area_acres" class="form-input" type="number" /></div>
          <div class="form-group"><label class="form-label">Farming goal</label><input v-model="editForm.farming_goal" class="form-input" /></div>
          <div class="form-group"><label class="form-label">Water source</label><input v-model="editForm.water_source" class="form-input" /></div>
          <div class="form-group"><label class="form-label">Notes</label><input v-model="editForm.notes" class="form-input" /></div>
        </div>
        <div class="flex-between"><button class="btn-secondary" @click="showEdit = false">Cancel</button><button class="btn-primary" @click="submitEdit">Save changes</button></div>
      </div>
    </div>
  </div>
</template>
