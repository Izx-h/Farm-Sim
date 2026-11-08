<script setup>
import { computed } from 'vue';

const props = defineProps({
  farm: { type: Object, required: true },
  evaluations: { type: Array, default: () => [] },
});

const emit = defineEmits(['select-crop-for-plan', 'open-economics']);

const topPick = computed(() => props.evaluations[0] || null);
const highlySuitable = computed(() => props.evaluations.filter((e) => e.suitabilityScore >= 80));
const recommended = computed(() => props.evaluations.filter((e) => e.isRecommended));
</script>

<template>
  <div>
    <div class="hero-banner">
      <span class="hero-badge-pill">✨ Personalized recommendation</span>
      <h1 class="hero-heading">
        {{ topPick ? `${topPick.cropName} is your top pick` : 'Crop suitability analysis' }}
      </h1>
      <p class="hero-desc">
        {{ topPick
          ? `Scored ${topPick.suitabilityScore}/100 against ${farm.soil_type}, pH ${farm.soil_ph}, ${farm.elevation_m}m altitude and ${farm.water_source}.`
          : 'Evaluating every crop in the catalogue against your farm\'s conditions.' }}
      </p>
      <div class="hero-stats-row" style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap">
        <div class="hero-stat-card">
          <div class="hero-stat-val">{{ highlySuitable.length }}</div>
          <div class="hero-stat-lbl">Highly suitable</div>
        </div>
        <div class="hero-stat-card">
          <div class="hero-stat-val">{{ recommended.length }}</div>
          <div class="hero-stat-lbl">Recommended</div>
        </div>
        <div class="hero-stat-card">
          <div class="hero-stat-val">{{ evaluations.length }}</div>
          <div class="hero-stat-lbl">Crops evaluated</div>
        </div>
      </div>
    </div>

    <div v-if="evaluations.length === 0" class="glass-card center-screen">
      <p>No suitability results yet.</p>
    </div>

    <div v-else class="crop-card-grid">
      <div
        v-for="e in evaluations"
        :key="e.cropId"
        class="crop-card"
        :class="{ 'highlight-top': e.cropId === topPick?.cropId }"
      >
        <div class="crop-card-header">
          <div>
            <div class="crop-title">{{ e.cropName }}</div>
            <div class="crop-latin">{{ e.cropDetails?.scientific_name }}</div>
          </div>
          <div class="score-badge" :class="e.badgeColor">
            {{ e.suitabilityScore }}
            <span class="score-badge-sub">{{ e.categoryLabel }}</span>
          </div>
        </div>

        <div class="progress-container">
          <div
            class="progress-bar-fill"
            :class="e.badgeColor"
            :style="{ width: e.suitabilityScore + '%' }"
          ></div>
        </div>

        <div class="bullet-points">
          <div v-for="(s, i) in e.strengths.slice(0, 2)" :key="'s' + i" class="bullet-item good">✓ {{ s }}</div>
          <div v-for="(l, i) in e.limitingFactors.slice(0, 2)" :key="'l' + i" class="bullet-item warning">⚠ {{ l }}</div>
          <div v-for="(iv, i) in e.interventions.slice(0, 1)" :key="'iv' + i" class="bullet-item">💡 {{ iv }}</div>
        </div>

        <div class="flex-between" style="margin-top: auto">
          <span class="small secondary">
            🌾 {{ e.projectedYieldTons }} t/ac · KSh {{ e.marketPricePerKg }}/kg
          </span>
          <span>
            <button class="btn-secondary btn-sm" @click="emit('open-economics', e.cropId)">📈</button>
            <button class="btn-primary btn-sm" @click="emit('select-crop-for-plan')">Plan</button>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
