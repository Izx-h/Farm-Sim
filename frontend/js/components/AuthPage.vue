<script setup>
import { ref } from 'vue';
import { api } from '../api';

const emit = defineEmits(['authenticated']);

const mode = ref('login');
const name = ref('');
const email = ref('');
const password = ref('');
const error = ref(null);
const busy = ref(false);

async function submit() {
  error.value = null;
  busy.value = true;
  try {
    const res =
      mode.value === 'login'
        ? await api.login(email.value, password.value)
        : await api.register(name.value, email.value, password.value);
    emit('authenticated', res.user);
  } catch (err) {
    error.value = err.message || 'Something went wrong.';
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-brand">
        <div class="brand-logo-icon">🌾</div>
        <div>
          <div class="brand-title">AgriPulse</div>
          <div class="brand-subtitle">Smart Agricultural Decisions</div>
        </div>
      </div>

      <h2 class="auth-heading">{{ mode === 'login' ? 'Welcome back' : 'Create your account' }}</h2>
      <p class="auth-sub">
        {{ mode === 'login'
          ? 'Log in to manage your farms, crop suitability, weather and financial planning.'
          : 'Register to get personalized, evidence-based farming recommendations.' }}
      </p>

      <div v-if="error" class="auth-error">⚠️ {{ error }}</div>

      <form class="auth-form" @submit.prevent="submit">
        <div v-if="mode === 'register'" class="form-group">
          <label class="form-label">Full name</label>
          <input v-model="name" class="form-input" type="text" placeholder="Your name" required />
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input v-model="email" class="form-input" type="email" placeholder="you@example.com" required />
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input v-model="password" class="form-input" type="password" placeholder="••••••••" required minlength="8" />
        </div>

        <button class="btn-primary auth-submit" type="submit" :disabled="busy">
          {{ busy ? 'Please wait…' : mode === 'login' ? 'Log in' : 'Create account' }}
        </button>
      </form>

      <div class="auth-switch">
        {{ mode === 'login' ? "Don't have an account?" : 'Already have an account?' }}
        <button type="button" @click="mode = mode === 'login' ? 'register' : 'login'">
          {{ mode === 'login' ? 'Register' : 'Log in' }}
        </button>
      </div>
    </div>
  </div>
</template>
