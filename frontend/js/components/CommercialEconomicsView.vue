<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  farm: { type: Object, required: true },
  crops: { type: Array, default: () => [] },
  marketPrices: { type: Array, default: () => [] },
  initialCropId: { type: String, default: undefined },
  simulate: { type: Function, required: true },
  importPrices: { type: Function, required: true },
});

const cropId = ref(props.initialCropId || '');
const acres = ref(props.farm?.area_acres || 1);
const result = ref(null);
const busy = ref(false);

async function run() {
  if (!cropId.value) return;
  busy.value = true;
  try {
    result.value = await props.simulate({ crop_id: cropId.value, acres: acres.value });
  } finally {
    busy.value = false;
  }
}

onMounted(() => {
  if (cropId.value) run();
});
watch(cropId, run);
watch(acres, run);

const fmt = (n) => (n == null ? '—' : `KSh ${Number(n).toLocaleString()}`);
const scenarioLabel = { conservative: '🟠 Conservative', expected: '🟢 Expected', optimistic: '🔵 Optimistic' };
</script>

<template>
  <div>
    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">📈 Commercial &amp; financial model</div></div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Crop</label>
          <select v-model="cropId" class="form-select">
            <option value="" disabled>Select a crop…</option>
            <option v-for="c in crops" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Acres: {{ acres }}</label>
          <input v-model.number="acres" class="custom-slider" type="range" min="0.5" max="50" step="0.5" />
        </div>
      </div>

      <div v-if="busy" class="small secondary">Calculating…</div>

      <template v-if="result && !busy">
        <div class="grid-3" style="margin-top: 1rem">
          <div v-for="key in ['conservative', 'expected', 'optimistic']" :key="key" class="crop-card">
            <div class="card-title" style="font-size: 1rem">{{ scenarioLabel[key] }}</div>
            <div class="small secondary">{{ result.scenarios[key].assumptions.note }}</div>
            <div class="bullet-points" style="margin-top: 0.5rem">
              <div class="bullet-item"><strong>Yield:</strong>&nbsp;{{ result.scenarios[key].yieldTons }} t</div>
              <div class="bullet-item"><strong>Revenue:</strong>&nbsp;{{ fmt(result.scenarios[key].revenue) }}</div>
              <div class="bullet-item good"><strong>Net profit:</strong>&nbsp;{{ fmt(result.scenarios[key].netProfit) }}</div>
              <div class="bullet-item"><strong>ROI:</strong>&nbsp;{{ result.scenarios[key].roiPercent }}%</div>
            </div>
          </div>
        </div>

        <div class="glass-card" style="margin-top: 1.5rem; margin-bottom: 0">
          <div class="card-title" style="font-size: 1rem">Break-even analysis</div>
          <div class="grid-2" style="margin-top: 0.5rem">
            <div class="overview-pill"><div class="overview-icon-box amber">💰</div><div><div class="small muted">Break-even price</div><strong>KSh {{ result.breakEven.pricePerKg }}/kg</strong></div></div>
            <div class="overview-pill"><div class="overview-icon-box emerald">⚖️</div><div><div class="small muted">Break-even yield</div><strong>{{ result.breakEven.yieldTons }} t</strong></div></div>
          </div>
        </div>
      </template>
    </div>

    <div class="glass-card">
      <div class="card-title">🏪 Regional commodity market board</div>
      <div class="market-source-banner" style="margin-top: 0.75rem">ℹ️ Prices are dated <em>reference</em> data (KSh). Import your own observations to update them.</div>
      <table class="data-table">
        <thead><tr><th>Crop</th><th>Market</th><th>Unit</th><th>Wholesale</th><th>Retail</th><th>Trend</th></tr></thead>
        <tbody>
          <tr v-for="m in marketPrices" :key="m.id">
            <td>{{ m.crop_name }}</td>
            <td>{{ m.market_name }}</td>
            <td>{{ m.unit }}</td>
            <td>{{ m.wholesale_price_ksh }}</td>
            <td>{{ m.retail_price_ksh }}</td>
            <td><span class="tag" :class="`tag-${m.price_trend === 'Rising' ? 'emerald' : m.price_trend === 'Falling' ? 'rose' : 'blue'}`">{{ m.price_trend }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
