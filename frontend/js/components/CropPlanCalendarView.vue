<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  farm: { type: Object, required: true },
  plans: { type: Array, default: () => [] },
  crops: { type: Array, default: () => [] },
  selectedPlanId: { type: String, default: null },
});

const emit = defineEmits(['select-plan', 'update-task-status', 'generate-plan']);

const showGenerate = ref(false);
const genCropId = ref('');
const genAcres = ref(props.farm?.area_acres || 1);
const genDate = ref(new Date().toISOString().split('T')[0]);

const selectedPlan = computed(() => props.plans.find((p) => p.id === props.selectedPlanId) || null);

function generate() {
  emit('generate-plan', genCropId.value, genAcres.value, genDate.value);
  showGenerate.value = false;
}

const stageOffsetLabel = (offset) => {
  if (offset < 0) return `${-offset}d before`;
  if (offset === 0) return 'Planting day';
  return `Day +${offset}`;
};
</script>

<template>
  <div>
    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">📅 Crop management plans</div></div>
        <button class="btn-primary" @click="showGenerate = true">➕ Generate plan</button>
      </div>

      <p v-if="!plans.length" class="small secondary">No plans yet. Generate one to get a growth-stage activity calendar.</p>

      <div v-else class="grid-2">
        <div>
          <div class="small muted" style="margin-bottom: 0.5rem">Plans</div>
          <div v-for="p in plans" :key="p.id" class="task-checkbox-item" :class="{ completed: false }" style="cursor: pointer" @click="emit('select-plan', p.id)">
            <div style="flex: 1">
              <div style="color: white; font-weight: 600">{{ p.crop_name }}</div>
              <div class="small secondary">{{ p.season_name }} · {{ p.status }}</div>
              <div class="small muted">{{ p.planted_area_acres }} acres</div>
            </div>
            <span v-if="p.id === selectedPlanId" class="tag tag-emerald">selected</span>
          </div>
        </div>

        <div>
          <div class="small muted" style="margin-bottom: 0.5rem">Task timeline</div>
          <div v-if="!selectedPlan" class="small secondary">Select a plan to see its task checklist.</div>
          <div v-else class="timeline-list">
            <div v-for="t in selectedPlan.tasks" :key="t.id" class="timeline-item">
              <div class="timeline-marker">✓</div>
              <div class="task-checkbox-item" :class="{ completed: t.status === 'Completed' }">
                <input
                  type="checkbox"
                  :checked="t.status === 'Completed'"
                  @change="emit('update-task-status', t.id, $event.target.checked ? 'Completed' : 'Pending')"
                />
                <div>
                  <div style="color: white; font-weight: 600">{{ t.title }}</div>
                  <div class="small secondary">{{ t.category }} · {{ stageOffsetLabel(t.due_day_offset) }}</div>
                  <div v-if="t.notes" class="small muted">{{ t.notes }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showGenerate" class="modal-overlay" @click.self="showGenerate = false">
      <div class="modal-content">
        <h3>Generate a crop plan</h3>
        <div class="form-group">
          <label class="form-label">Crop</label>
          <select v-model="genCropId" class="form-select">
            <option v-for="c in crops" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Planted area (acres)</label>
            <input v-model.number="genAcres" class="form-input" type="number" min="0.1" step="0.1" />
          </div>
          <div class="form-group">
            <label class="form-label">Planting date</label>
            <input v-model="genDate" class="form-input" type="date" />
          </div>
        </div>
        <div class="flex-between">
          <button class="btn-secondary" @click="showGenerate = false">Cancel</button>
          <button class="btn-primary" :disabled="!genCropId" @click="generate">Generate</button>
        </div>
      </div>
    </div>
  </div>
</template>
