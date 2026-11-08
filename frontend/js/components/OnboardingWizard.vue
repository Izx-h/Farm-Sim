<script setup>
import { ref, reactive } from 'vue';
import { api } from '../api';
import FarmMapSelector from './FarmMapSelector.vue';

const emit = defineEmits(['complete']);

const step = ref(0);
const busy = ref(false);
const error = ref(null);

const geo = ref(null);
const lat = ref(-0.3031);
const lon = ref(36.08);

const form = reactive({
  name: '',
  owner: '',
  location_name: '',
  area_acres: 1,
  farming_goal: 'Mixed',
  water_source: 'Rainfed',
  soil_type: 'Loam',
  soil_ph: 6.2,
  slope_deg: 3.0,
  elevation_m: 1800,
  nitrogen_level: 'Medium',
  phosphorus_level: 'Medium',
  potassium_level: 'Medium',
  organic_matter_pct: 3.0,
  drainage_class: 'Well-drained',
  notes: '',
});

const includeSoilTest = ref(false);
const includeWaterTest = ref(false);
const soilTest = reactive({ ph: '', n_ppm: '', p_ppm: '', k_ppm: '', laboratory: '' });
const waterTest = reactive({ ph: '', ec_ds_m: '', tds_mg_l: '', fluoride_mg_l: '', laboratory: '' });

async function handleLookup({ lat: la, lng }) {
  lat.value = la;
  lon.value = lng;
  try {
    geo.value = await api.lookupLocation(la, lng);
    form.location_name = geo.value.location_name || '';
    form.soil_type = geo.value.soil_type || form.soil_type;
    form.soil_ph = geo.value.soil_ph ?? form.soil_ph;
    form.slope_deg = geo.value.slope_deg ?? form.slope_deg;
    form.elevation_m = geo.value.elevation_m ?? form.elevation_m;
    form.nitrogen_level = geo.value.nitrogen_level || form.nitrogen_level;
    form.phosphorus_level = geo.value.phosphorus_level || form.phosphorus_level;
    form.potassium_level = geo.value.potassium_level || form.potassium_level;
    form.organic_matter_pct = geo.value.organic_matter_pct ?? form.organic_matter_pct;
    form.drainage_class = geo.value.drainage_class || form.drainage_class;
    form.water_source = geo.value.water_source || form.water_source;
    form.farming_goal = geo.value.farming_goal || form.farming_goal;
  } catch (err) {
    console.error(err);
  }
}

async function save() {
  busy.value = true;
  error.value = null;
  try {
    const payload = {
      ...form,
      latitude: lat.value,
      longitude: lon.value,
    };
    const farm = await api.createFarm(payload);

    if (includeSoilTest.value) await api.addSoilTest(farm.id, soilTest);
    if (includeWaterTest.value) await api.addWaterTest(farm.id, waterTest);

    await api.completeOnboarding();
    emit('complete', farm);
  } catch (err) {
    error.value = err.message || 'Failed to save farm.';
  } finally {
    busy.value = false;
  }
}

const goalOptions = ['Subsistence', 'Commercial', 'Mixed'];
const waterOptions = ['Rainfed', 'Borehole/Well', 'River/Stream', 'Drip Irrigation', 'Borehole & Drip', 'Rainfed & River'];
const levelOptions = ['Low', 'Medium', 'High'];
const drainageOptions = ['Well-drained', 'Moderate', 'Poor', 'Excessively drained'];
</script>

<template>
  <div class="onboarding-shell">
    <div class="onboarding-card">
      <div class="onboarding-header">
        <div class="brand-title" style="font-size: 1.3rem">🌾 AgriPulse</div>
        <div class="onboarding-step-indicator">Step {{ step + 1 }} of 5</div>
      </div>

      <div class="ob-body">
        <!-- Step 0: intro -->
        <template v-if="step === 0">
          <h2>Welcome to AgriPulse 👋</h2>
          <p>Set up your first farm to unlock crop suitability, weather, groundwater and financial intelligence.</p>
          <div class="ob-points">
            <div>✅ Locate your farm on the map</div>
            <div>✅ Get auto-detected soil &amp; groundwater</div>
            <div>✅ Receive crop and planting recommendations</div>
          </div>
          <div class="ob-actions">
            <span></span>
            <button class="btn-primary" @click="step = 1">Get started →</button>
          </div>
        </template>

        <!-- Step 1: locate -->
        <template v-else-if="step === 1">
          <h2>Where is your farm?</h2>
          <p>Click anywhere on the map to drop a pin. AgriPulse will reverse-geocode the location and infer the soil, terrain and groundwater profile.</p>
          <FarmMapSelector :latitude="lat" :longitude="lon" @lookup="handleLookup" />
          <div v-if="geo" class="ob-geo-summary">
            <div class="ob-section-title">📍 {{ geo.location_name }}</div>
            <div class="ob-geo-grid">
              <div><strong>{{ geo.elevation_m }}m</strong> elevation</div>
              <div><strong>{{ geo.slope_deg }}°</strong> slope</div>
              <div><strong>{{ geo.soil_ph }}</strong> pH</div>
              <div><strong>{{ geo.soil_type }}</strong></div>
              <div><strong>{{ geo.groundwater?.aquifer_name }}</strong></div>
            </div>
            <div class="ob-estimated-tag">ℹ️ These are <em>estimated</em> regional values — not laboratory results.</div>
          </div>
          <div class="ob-actions">
            <button class="btn-secondary" @click="step = 0">← Back</button>
            <button class="btn-primary" @click="step = 2">Continue →</button>
          </div>
        </template>

        <!-- Step 2: farm details -->
        <template v-else-if="step === 2">
          <h2>Farm details</h2>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Farm name</label>
              <input v-model="form.name" class="form-input" placeholder="Green Horizon Valley" />
            </div>
            <div class="form-group">
              <label class="form-label">Owner</label>
              <input v-model="form.owner" class="form-input" placeholder="Your name" />
            </div>
            <div class="form-group">
              <label class="form-label">Location name</label>
              <input v-model="form.location_name" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Area (acres)</label>
              <input v-model.number="form.area_acres" class="form-input" type="number" min="0.1" step="0.1" />
            </div>
            <div class="form-group">
              <label class="form-label">Farming objective</label>
              <select v-model="form.farming_goal" class="form-select">
                <option v-for="g in goalOptions" :key="g">{{ g }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Water source</label>
              <select v-model="form.water_source" class="form-select">
                <option v-for="w in waterOptions" :key="w">{{ w }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Soil type</label>
              <input v-model="form.soil_type" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Soil pH</label>
              <input v-model.number="form.soil_ph" class="form-input" type="number" step="0.1" />
            </div>
            <div class="form-group">
              <label class="form-label">Slope (degrees)</label>
              <input v-model.number="form.slope_deg" class="form-input" type="number" step="0.5" />
            </div>
            <div class="form-group">
              <label class="form-label">Elevation (m)</label>
              <input v-model.number="form.elevation_m" class="form-input" type="number" step="10" />
            </div>
          </div>
          <div class="ob-actions">
            <button class="btn-secondary" @click="step = 1">← Back</button>
            <button class="btn-primary" @click="step = 3">Continue →</button>
          </div>
        </template>

        <!-- Step 3: optional tests -->
        <template v-else-if="step === 3">
          <h2>Laboratory results (optional)</h2>
          <p class="ob-sub">Add verified lab results if you have them. You can skip this — AgriPulse works with estimated values.</p>

          <div class="ob-section">
            <label class="ob-toggle">
              <input type="checkbox" v-model="includeSoilTest" /> Include a soil test
            </label>
            <template v-if="includeSoilTest">
              <div class="form-grid-2">
                <div class="form-group"><label class="form-label">pH</label><input v-model="soilTest.ph" class="form-input" type="number" step="0.1" /></div>
                <div class="form-group"><label class="form-label">N (ppm)</label><input v-model="soilTest.n_ppm" class="form-input" type="number" /></div>
                <div class="form-group"><label class="form-label">P (ppm)</label><input v-model="soilTest.p_ppm" class="form-input" type="number" /></div>
                <div class="form-group"><label class="form-label">K (ppm)</label><input v-model="soilTest.k_ppm" class="form-input" type="number" /></div>
                <div class="form-group"><label class="form-label">Laboratory</label><input v-model="soilTest.laboratory" class="form-input" /></div>
              </div>
            </template>
          </div>

          <div class="ob-section">
            <label class="ob-toggle">
              <input type="checkbox" v-model="includeWaterTest" /> Include a water test
            </label>
            <template v-if="includeWaterTest">
              <div class="form-grid-2">
                <div class="form-group"><label class="form-label">pH</label><input v-model="waterTest.ph" class="form-input" type="number" step="0.1" /></div>
                <div class="form-group"><label class="form-label">EC (dS/m)</label><input v-model="waterTest.ec_ds_m" class="form-input" type="number" step="0.01" /></div>
                <div class="form-group"><label class="form-label">TDS (mg/L)</label><input v-model="waterTest.tds_mg_l" class="form-input" type="number" /></div>
                <div class="form-group"><label class="form-label">Fluoride (mg/L)</label><input v-model="waterTest.fluoride_mg_l" class="form-input" type="number" step="0.1" /></div>
                <div class="form-group"><label class="form-label">Laboratory</label><input v-model="waterTest.laboratory" class="form-input" /></div>
              </div>
            </template>
          </div>

          <div class="ob-actions">
            <button class="btn-secondary" @click="step = 2">← Back</button>
            <button class="btn-primary" @click="step = 4">Review →</button>
          </div>
        </template>

        <!-- Step 4: review -->
        <template v-else>
          <h2>Review &amp; save</h2>
          <div class="ob-review">
            <div class="ob-review-row"><span>Farm name</span><strong>{{ form.name || 'My Farm' }}</strong></div>
            <div class="ob-review-row"><span>Location</span><strong>{{ form.location_name || `${lat}, ${lon}` }}</strong></div>
            <div class="ob-review-row"><span>Area</span><strong>{{ form.area_acres }} acres</strong></div>
            <div class="ob-review-row"><span>Objective</span><strong>{{ form.farming_goal }}</strong></div>
            <div class="ob-review-row"><span>Soil</span><strong>{{ form.soil_type }} · pH {{ form.soil_ph }}</strong></div>
            <div class="ob-review-row"><span>Water</span><strong>{{ form.water_source }}</strong></div>
          </div>
          <div v-if="error" class="auth-error">⚠️ {{ error }}</div>
          <div class="ob-actions">
            <button class="btn-secondary" @click="step = 3">← Back</button>
            <button class="btn-primary" :disabled="busy" @click="save">{{ busy ? 'Saving…' : 'Save farm' }}</button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
