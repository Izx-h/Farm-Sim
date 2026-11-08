<script setup>
import { ref } from 'vue';

const props = defineProps({
  farm: { type: Object, required: true },
  pests: { type: Array, default: () => [] },
  alerts: { type: Array, default: () => [] },
  diagnose: { type: Function, required: true },
});

const cropName = ref('');
const plantPart = ref('');
const symptoms = ref('');
const result = ref(null);
const busy = ref(false);

async function runDiagnose() {
  busy.value = true;
  try {
    result.value = await props.diagnose({
      cropName: cropName.value,
      plantPart: plantPart.value,
      observedSymptoms: symptoms.value,
    });
  } finally {
    busy.value = false;
  }
}

const alertTag = (level) => (level === 'Emergency' ? 'rose' : level === 'Warning' ? 'amber' : 'blue');
</script>

<template>
  <div>
    <div class="glass-card">
      <div class="card-title">🚨 Pest &amp; disease early-warning</div>
      <div class="card-subtitle">Regional surveillance alerts</div>
      <div v-for="a in alerts" :key="a.id" class="task-checkbox-item" style="margin-top: 0.75rem">
        <div style="flex: 1">
          <div class="flex-between">
            <strong style="color: white">{{ a.pest_name }}</strong>
            <span class="tag" :class="`tag-${alertTag(a.alert_level)}`">{{ a.alert_level }}</span>
          </div>
          <div class="small secondary">{{ a.region }} · {{ a.reported_date }}</div>
          <div class="small" style="margin-top: 0.4rem">{{ a.summary }}</div>
          <div class="small secondary" style="margin-top: 0.4rem"><strong>Action:</strong> {{ a.action_required }}</div>
        </div>
      </div>
    </div>

    <div class="glass-card">
      <div class="card-title">🔍 Diagnostic symptom checker</div>
      <div class="card-subtitle">Describe the symptoms to match against the built-in pest reference library.</div>
      <div class="form-grid-2" style="margin-top: 1rem">
        <div class="form-group"><label class="form-label">Crop</label><input v-model="cropName" class="form-input" placeholder="e.g. Maize" /></div>
        <div class="form-group"><label class="form-label">Plant part</label><input v-model="plantPart" class="form-input" placeholder="e.g. leaves" /></div>
      </div>
      <div class="form-group">
        <label class="form-label">Observed symptoms</label>
        <textarea v-model="symptoms" class="form-textarea" rows="3" placeholder="e.g. windowpane holes, sawdust-like frass, wilting"></textarea>
      </div>
      <button class="btn-primary" :disabled="busy || !symptoms" @click="runDiagnose">{{ busy ? 'Analyzing…' : 'Diagnose' }}</button>

      <div v-if="result" style="margin-top: 1.25rem">
        <div class="card-title" style="font-size: 1rem">Top diagnosis</div>
        <div v-if="result.topDiagnosis" class="task-checkbox-item" style="margin-top: 0.5rem">
          <div style="flex: 1">
            <div class="flex-between">
              <strong style="color: white">{{ result.topDiagnosis.name }}</strong>
              <span class="tag tag-emerald">{{ result.topDiagnosis.confidenceScore }}% match</span>
            </div>
            <div class="small secondary">{{ result.topDiagnosis.type }} · {{ result.topDiagnosis.affected_crops }}</div>
            <div class="small" style="margin-top: 0.4rem"><strong>Prevention:</strong> {{ result.topDiagnosis.prevention_measures }}</div>
            <div class="small" style="margin-top: 0.4rem"><strong>Chemical:</strong> {{ result.topDiagnosis.chemical_control }}</div>
            <div class="small" style="margin-top: 0.4rem"><strong>Biological:</strong> {{ result.topDiagnosis.biological_control }}</div>
          </div>
        </div>
        <p v-else class="small secondary">No close match found.</p>
        <div class="data-classification-banner" style="margin-top: 0.75rem; margin-bottom: 0">{{ result.disclaimer }}</div>
      </div>
    </div>
  </div>
</template>
