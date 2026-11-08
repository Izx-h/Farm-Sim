<script setup>
import { computed } from 'vue';

const props = defineProps({
  farm: { type: Object, required: true },
  weather: { type: Object, required: true },
});

const days = computed(() => props.weather?.forecastDays || []);
const advisory = computed(() => props.weather?.advisory || null);
const current = computed(() => props.weather?.current || null);

const suitTag = (s) => {
  if (!s) return 'blue';
  if (s.startsWith('Favourable')) return 'emerald';
  if (s.startsWith('Delay') || s.includes('Heat')) return 'rose';
  if (s.includes('Rain')) return 'blue';
  return 'amber';
};
</script>

<template>
  <div>
    <div class="glass-card">
      <div class="card-header-clean">
        <div><div class="card-title">☁️ Seasonal forecast &amp; planting advisory</div></div>
        <span v-if="advisory" class="tag tag-emerald">{{ advisory.seasonName }}</span>
      </div>

      <div v-if="weather.dataStatus === 'unavailable'" class="data-classification-banner">
        ⚠️ Weather service unreachable — {{ weather.error }}
      </div>

      <template v-else>
        <div class="grid-3">
          <div class="overview-pill"><div class="overview-icon-box blue">🌡️</div><div><div class="small muted">Current temp</div><strong>{{ current?.tempC ?? '—' }}°C</strong><div class="small secondary">{{ current?.condition }}</div></div></div>
          <div class="overview-pill"><div class="overview-icon-box emerald">💦</div><div><div class="small muted">Humidity</div><strong>{{ current?.humidityPct ?? '—' }}%</strong></div></div>
          <div class="overview-pill"><div class="overview-icon-box amber">🌧️</div><div><div class="small muted">7-day rain</div><strong>{{ advisory?.derivedMetrics?.totalRain7dMm ?? '—' }} mm</strong><div class="small secondary">{{ advisory?.derivedMetrics?.rainDays ?? 0 }} rain days</div></div></div>
        </div>

        <div v-if="advisory" class="glass-card" style="margin-top: 1.5rem; margin-bottom: 0">
          <div class="card-title">{{ advisory.seasonalStatus }}</div>
          <p class="secondary" style="margin-top: 0.5rem">{{ advisory.advisoryRecommendation }}</p>
          <div v-if="advisory.riskAlerts.length" class="bullet-points" style="margin-top: 0.75rem">
            <div v-for="(r, i) in advisory.riskAlerts" :key="i" class="bullet-item danger">⚠ {{ r }}</div>
          </div>
          <div class="data-classification-banner" style="margin-top: 1rem; margin-bottom: 0">
            ℹ️ {{ advisory.derivedMetrics.note }}
          </div>
        </div>

        <div class="weather-strip">
          <div v-for="d in days" :key="d.date" class="weather-day-card">
            <div class="weather-day-name">{{ d.dayName }}</div>
            <div style="font-size: 1.3rem">{{ d.rainMm > 0 ? '🌧️' : '☀️' }}</div>
            <div class="weather-day-rain">{{ d.rainMm }} mm</div>
            <div class="weather-day-temp">{{ d.tempMax }}° / {{ d.tempMin }}°</div>
            <span class="tag" :class="`tag-${suitTag(d.plantingSuitability)}`">{{ d.plantingSuitability }}</span>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
