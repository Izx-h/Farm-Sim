<script setup>
import { ref, reactive } from 'vue';

const props = defineProps({
  farm: { type: Object, required: true },
  specialists: { type: Array, default: () => [] },
  submitConsultation: { type: Function, required: true },
});

const showModal = ref(false);
const selected = ref(null);
const busy = ref(false);
const sent = ref(false);
const form = reactive({ inquiry_type: 'Crop Planning', subject: '', message: '' });

function open(s) {
  selected.value = s;
  showModal.value = true;
  sent.value = false;
  form.subject = '';
  form.message = '';
}

async function submit() {
  busy.value = true;
  try {
    await props.submitConsultation({
      farm_id: props.farm.id,
      specialist_id: selected.value.id,
      inquiry_type: form.inquiry_type,
      subject: form.subject,
      message: form.message,
    });
    sent.value = true;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div>
    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">👩‍🔬 Agricultural specialists &amp; laboratories</div></div>
      </div>
      <div class="crop-card-grid">
        <div v-for="s in specialists" :key="s.id" class="crop-card">
          <div class="flex-between">
            <div class="card-title" style="font-size: 1rem">{{ s.name }}</div>
            <span class="tag tag-emerald">★ {{ s.rating }}</span>
          </div>
          <div class="small secondary">{{ s.role }}</div>
          <div class="small muted">{{ s.organization }}</div>
          <div class="small" style="margin-top: 0.5rem">{{ s.specialization }}</div>
          <div class="small secondary">📍 {{ s.location }} · {{ s.experience_years }} yrs · {{ s.consultations_count }} consultations</div>
          <button class="btn-primary btn-sm" @click="open(s)">Request review</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content">
        <h3>Request specialist review</h3>
        <p class="small secondary" style="margin-top: 0.25rem">Contact {{ selected?.name }} about "{{ farm.name }}".</p>

        <div v-if="sent" class="auth-error" style="border-color: rgba(16,185,129,0.4); background: var(--emerald-900); color: #a7f3d0">
          ✅ Consultation request submitted successfully.
        </div>

        <template v-else>
          <div class="form-group">
            <label class="form-label">Inquiry type</label>
            <select v-model="form.inquiry_type" class="form-select">
              <option>Soil Test Review</option>
              <option>Pest/Disease Diagnosis</option>
              <option>Crop Planning</option>
              <option>Irrigation Design</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Subject</label>
            <input v-model="form.subject" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea v-model="form.message" class="form-textarea" rows="4"></textarea>
          </div>
          <div class="flex-between">
            <button class="btn-secondary" @click="showModal = false">Cancel</button>
            <button class="btn-primary" :disabled="busy" @click="submit">{{ busy ? 'Sending…' : 'Submit request' }}</button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
