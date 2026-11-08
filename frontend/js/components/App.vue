<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { api, getToken } from '../api';
import AuthPage from './AuthPage.vue';
import OnboardingWizard from './OnboardingWizard.vue';
import CropSuitabilityView from './CropSuitabilityView.vue';
import FarmProfileView from './FarmProfileView.vue';
import GroundwaterIntelligenceView from './GroundwaterIntelligenceView.vue';
import WeatherPlantingView from './WeatherPlantingView.vue';
import CropPlanCalendarView from './CropPlanCalendarView.vue';
import PestDiseaseAlertsView from './PestDiseaseAlertsView.vue';
import CommercialEconomicsView from './CommercialEconomicsView.vue';
import SpecialistDirectoryView from './SpecialistDirectoryView.vue';

const user = ref(null);
const authChecking = ref(true);
const showOnboarding = ref(false);

const activeTab = ref('suitability');
const farms = ref([]);
const activeFarmId = ref('');
const crops = ref([]);
const evaluations = ref([]);
const weather = ref(null);
const plans = ref([]);
const selectedPlanId = ref(null);
const pests = ref([]);
const alerts = ref([]);
const marketPrices = ref([]);
const specialists = ref([]);
const economicCropId = ref(undefined);
const loading = ref(true);
const notification = ref(null);

const tabs = [
  { id: 'suitability', label: 'Crop Suitability', icon: '✨' },
  { id: 'profiling', label: 'Farm Profiling', icon: '🧭' },
  { id: 'groundwater', label: 'Groundwater', icon: '💧' },
  { id: 'weather', label: 'Forecast & Planting', icon: '☁️' },
  { id: 'calendar', label: 'Crop Plans', icon: '📅' },
  { id: 'pests', label: 'Pests & Diseases', icon: '🐛' },
  { id: 'economics', label: 'Financial Model', icon: '📈' },
  { id: 'specialists', label: 'Specialists', icon: '👩‍🔬' },
];

const activeFarm = computed(() => farms.value.find((f) => f.id === activeFarmId.value) || farms.value[0]);

let toastTimer = null;
function showToast(msg) {
  notification.value = msg;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (notification.value = null), 4000);
}

function handleUnauthorized() {
  user.value = null;
  farms.value = [];
  activeFarmId.value = '';
}

async function loadAllInitialData() {
  try {
    loading.value = true;
    const [f, c, pestData, prices, specs] = await Promise.all([
      api.getFarms(),
      api.getCrops(),
      api.getPestsAndAlerts(),
      api.getMarketPrices(),
      api.getSpecialists(),
    ]);
    farms.value = f;
    crops.value = c;
    pests.value = pestData.pests;
    alerts.value = pestData.alerts;
    marketPrices.value = prices;
    specialists.value = specs;

    if (f.length > 0) {
      activeFarmId.value = f[0].id;
      await loadFarmData(f[0].id);
    }
  } catch (err) {
    console.error('Failed to load initial data:', err);
  } finally {
    loading.value = false;
  }
}

async function loadFarmData(farmId) {
  try {
    const [suitability, weatherData, allPlans] = await Promise.all([
      api.getFarmSuitability(farmId),
      api.getFarmWeather(farmId),
      api.getPlans(),
    ]);
    evaluations.value = suitability.evaluations;
    weather.value = weatherData;
    const farmPlans = allPlans.filter((p) => p.farm_id === farmId);
    plans.value = farmPlans;
    if (farmPlans.length > 0 && (!selectedPlanId.value || !farmPlans.some((p) => p.id === selectedPlanId.value))) {
      selectedPlanId.value = farmPlans[0].id;
    }
  } catch (err) {
    console.error('Error fetching farm details:', err);
  }
}

async function handleFarmSwitch(farmId) {
  activeFarmId.value = farmId;
  await loadFarmData(farmId);
  const f = farms.value.find((x) => x.id === farmId);
  showToast(`Switched to ${f?.name || 'farm'}`);
}

function handleAuthenticated(u) {
  user.value = u;
  loadAllInitialData();
}

async function handleOnboardComplete(farm) {
  user.value = { ...user.value, onboarding_completed: 1 };
  showOnboarding.value = false;
  farms.value = await api.getFarms();
  if (farm?.id) {
    activeFarmId.value = farm.id;
    await loadFarmData(farm.id);
    showToast(`Farm "${farm.name}" saved successfully!`);
  }
}

async function handleLogout() {
  await api.logout();
  user.value = null;
  farms.value = [];
  activeFarmId.value = '';
  activeTab.value = 'suitability';
}

function handleSelectCropForPlan() {
  activeTab.value = 'calendar';
}
function handleOpenEconomics(cropId) {
  economicCropId.value = cropId;
  activeTab.value = 'economics';
}

async function handleGeneratePlan(cropId, acres, plantingDate) {
  if (!activeFarm.value) return;
  const newPlan = await api.generatePlan({
    farm_id: activeFarm.value.id,
    crop_id: cropId,
    planted_area_acres: acres,
    target_planting_date: plantingDate,
    season_name: 'Upcoming Season',
  });
  await loadFarmData(activeFarm.value.id);
  selectedPlanId.value = newPlan.id;
  showToast(`Agronomic plan generated for ${newPlan.crop_name}!`);
}

async function handleUpdateTaskStatus(taskId, status, notes) {
  await api.updateTaskStatus(taskId, status, notes);
  if (activeFarm.value) {
    const all = await api.getPlans();
    plans.value = all.filter((p) => p.farm_id === activeFarm.value.id);
  }
}

async function handleAddSoilTest(farmId, data) {
  await api.addSoilTest(farmId, data);
  farms.value = await api.getFarms();
  await loadFarmData(farmId);
  showToast('Soil laboratory report recorded and farm profile updated!');
}

async function handleAddWaterTest(farmId, data) {
  await api.addWaterTest(farmId, data);
  farms.value = await api.getFarms();
  await loadFarmData(farmId);
  showToast('Certified groundwater quality laboratory test recorded!');
}

async function handleAddNewFarm(data) {
  const created = await api.createFarm(data);
  farms.value = await api.getFarms();
  activeFarmId.value = created.id;
  await loadFarmData(created.id);
  showToast(`New parcel "${created.name}" registered successfully!`);
}

async function handleUpdateFarm(farmId, data) {
  await api.updateFarm(farmId, data);
  farms.value = await api.getFarms();
  showToast('Farm details updated.');
}

onMounted(async () => {
  window.addEventListener('agripulse:unauthorized', handleUnauthorized);
  if (!getToken()) {
    authChecking.value = false;
    return;
  }
  try {
    user.value = await api.me();
  } catch {
    user.value = null;
  } finally {
    authChecking.value = false;
    if (user.value) await loadAllInitialData();
  }
});

onBeforeUnmount(() => window.removeEventListener('agripulse:unauthorized', handleUnauthorized));
</script>

<template>
  <div v-if="authChecking" class="auth-shell">
    <div class="spin" style="font-size: 2rem">🌱</div>
  </div>

  <AuthPage v-else-if="!user" @authenticated="handleAuthenticated" />

  <OnboardingWizard v-else-if="user.onboarding_completed === 0 || showOnboarding" @complete="handleOnboardComplete" />

  <div v-else class="app-container">
    <div v-if="notification" class="toast">✨ {{ notification }}</div>

    <header class="top-navbar">
      <div class="brand-section">
        <div class="brand-logo-icon">🌾</div>
        <div>
          <div class="brand-title">AgriPulse</div>
          <div class="brand-subtitle">Smart Agricultural Decision Platform</div>
        </div>
      </div>

      <div class="navbar-center">
        <div v-if="farms.length > 0" class="active-farm-selector">
          <span>🧭</span>
          <select class="farm-select-dropdown" :value="activeFarmId" @change="handleFarmSwitch($event.target.value)">
            <option v-for="f in farms" :key="f.id" :value="f.id">{{ f.name }} ({{ f.location_name }})</option>
          </select>
        </div>

        <div v-if="weather?.advisory" class="season-badge">
          <span class="pulse-dot"></span><span>{{ weather.advisory.seasonalStatus }}</span>
        </div>

        <button class="btn-secondary btn-sm" @click="showOnboarding = true">➕ Add farm</button>

        <div class="user-menu">
          <span class="user-name" :title="user.email">{{ user.name }}</span>
          <button class="btn-secondary btn-sm" @click="handleLogout" title="Log out">🚪</button>
        </div>
      </div>
    </header>

    <nav class="nav-tabs-bar">
      <button
        v-for="t in tabs"
        :key="t.id"
        class="tab-btn"
        :class="{ active: activeTab === t.id }"
        @click="activeTab = t.id"
      >
        <span>{{ t.icon }}</span> {{ t.label }}
      </button>
    </nav>

    <main class="main-wrapper">
      <div v-if="loading" class="center-screen">
        <div class="spin" style="font-size: 2rem">🔄</div>
        <h3 style="color: white; margin-top: 1rem">Analyzing Farm Environmental Parameters...</h3>
        <p class="small">Integrating soil chemistry, climate forecasts, and crop agronomics.</p>
      </div>

      <div v-else-if="!activeFarm" class="glass-card center-screen">
        <div style="font-size: 3rem">📍</div>
        <h3 style="color: white; margin: 0.5rem 0">No farms yet</h3>
        <p class="secondary" style="max-width: 420px; margin: 0 auto 1.5rem">
          Add your first farm to unlock personalized crop suitability, weather forecasts and financial planning.
        </p>
        <button class="btn-primary" @click="showOnboarding = true">➕ Add your farm</button>
      </div>

      <template v-else>
        <div class="quick-overview-bar">
          <div class="overview-pill">
            <div class="overview-icon-box emerald">🧪</div>
            <div>
              <div class="small" style="color: var(--text-muted); text-transform: uppercase">Soil pH & Type</div>
              <strong style="color: white">{{ activeFarm.soil_ph }} pH</strong>
              <span class="small secondary"> · {{ activeFarm.soil_type }}</span>
            </div>
          </div>
          <div class="overview-pill">
            <div class="overview-icon-box blue">⛰️</div>
            <div>
              <div class="small" style="color: var(--text-muted); text-transform: uppercase">Elevation & Slope</div>
              <strong style="color: white">{{ activeFarm.elevation_m }}m</strong>
              <span class="small secondary"> · {{ activeFarm.slope_deg }}° slope</span>
            </div>
          </div>
          <div class="overview-pill" style="cursor: pointer" @click="activeTab = 'groundwater'">
            <div class="overview-icon-box amber">💧</div>
            <div>
              <div class="small" style="color: var(--text-muted); text-transform: uppercase">Water & Aquifer</div>
              <strong style="color: white">{{ activeFarm.water_source }}</strong>
              <div class="small" style="color: var(--blue-400)">
                {{ activeFarm.groundwater_salinity_class || activeFarm.groundwater?.salinity_category || 'Fresh' }}
                (~{{ activeFarm.groundwater_depth_m || activeFarm.groundwater?.water_table_depth_m || 42 }}m)
              </div>
            </div>
          </div>
          <div class="overview-pill">
            <div class="overview-icon-box purple">📐</div>
            <div>
              <div class="small" style="color: var(--text-muted); text-transform: uppercase">Parcel Area & Goal</div>
              <strong style="color: white">{{ activeFarm.area_acres }} Acres</strong>
              <span class="small secondary"> · {{ activeFarm.farming_goal }}</span>
            </div>
          </div>
        </div>

        <CropSuitabilityView
          v-if="activeTab === 'suitability'"
          :farm="activeFarm"
          :evaluations="evaluations"
          @select-crop-for-plan="handleSelectCropForPlan"
          @open-economics="handleOpenEconomics"
        />
        <FarmProfileView
          v-else-if="activeTab === 'profiling'"
          :farm="activeFarm"
          :farms="farms"
          @select-farm="handleFarmSwitch"
          @refresh-farm="loadFarmData(activeFarm.id)"
          @add-new-farm="handleAddNewFarm"
          @add-soil-test="handleAddSoilTest"
          @add-water-test="handleAddWaterTest"
          @update-farm="handleUpdateFarm"
        />
        <GroundwaterIntelligenceView
          v-else-if="activeTab === 'groundwater'"
          :farm="activeFarm"
          :crops="crops"
          @select-farm="handleFarmSwitch"
        />
        <WeatherPlantingView v-else-if="activeTab === 'weather' && weather" :farm="activeFarm" :weather="weather" />
        <CropPlanCalendarView
          v-else-if="activeTab === 'calendar'"
          :farm="activeFarm"
          :plans="plans"
          :crops="crops"
          :selected-plan-id="selectedPlanId"
          @select-plan="(id) => (selectedPlanId = id)"
          @update-task-status="handleUpdateTaskStatus"
          @generate-plan="handleGeneratePlan"
        />
        <PestDiseaseAlertsView
          v-else-if="activeTab === 'pests'"
          :farm="activeFarm"
          :pests="pests"
          :alerts="alerts"
          :diagnose="api.diagnosePest"
        />
        <CommercialEconomicsView
          v-else-if="activeTab === 'economics'"
          :farm="activeFarm"
          :crops="crops"
          :market-prices="marketPrices"
          :initial-crop-id="economicCropId"
          :simulate="api.simulateEconomics"
          :import-prices="api.importMarketPrices"
        />
        <SpecialistDirectoryView
          v-else-if="activeTab === 'specialists'"
          :farm="activeFarm"
          :specialists="specialists"
          :submit-consultation="api.submitConsultation"
        />
      </template>
    </main>
  </div>
</template>
