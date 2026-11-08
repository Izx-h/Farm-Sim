/**
 * Central API client. Calls the Laravel API on the same origin (`/api`) by
 * default; override with VITE_API_BASE if the UI and API are split.
 */
const envBase = (import.meta.env.VITE_API_BASE || '').trim();

const API_BASE = envBase ? envBase.replace(/\/$/, '') : '/api';

export function getApiBase() {
  return API_BASE;
}

if (typeof console !== 'undefined') {
  console.info(`[AgriPulse] API base: ${API_BASE}`);
}

async function apiFetch(path, init = {}) {
  try {
    return await fetch(`${API_BASE}${path}`, init);
  } catch {
    throw new Error(
      `Cannot reach the AgriPulse API at ${API_BASE}. Ensure the Laravel backend is running.`
    );
  }
}

async function readJson(res) {
  const raw = await res.text();
  if (!raw) return {};
  try {
    return JSON.parse(raw);
  } catch {
    return {};
  }
}

// ---- token management (localStorage) ----
const TOKEN_KEY = 'agripulse_token';

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}
export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token);
}
export function clearToken() {
  localStorage.removeItem(TOKEN_KEY);
}

function authHeaders() {
  const headers = { 'Content-Type': 'application/json' };
  const token = getToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;
  return headers;
}

async function request(path, options = {}) {
  const res = await apiFetch(path, {
    ...options,
    headers: { ...authHeaders(), ...(options.headers || {}) },
  });

  if (res.status === 401) {
    clearToken();
    if (typeof window !== 'undefined') window.dispatchEvent(new Event('agripulse:unauthorized'));
  }

  const data = await readJson(res);
  if (!res.ok) {
    throw new Error(data?.error || `Request failed (${res.status})`);
  }
  return data;
}

export const api = {
  // Auth
  async register(name, email, password) {
    const res = await apiFetch('/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, password }),
    });
    const data = await readJson(res);
    if (!res.ok) throw new Error(data?.error || 'Registration failed');
    setToken(data.token);
    return data;
  },
  async login(email, password) {
    const res = await apiFetch('/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    const data = await readJson(res);
    if (!res.ok) throw new Error(data?.error || 'Login failed');
    setToken(data.token);
    return data;
  },
  async logout() {
    const token = getToken();
    try {
      await apiFetch('/auth/logout', {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
      });
    } finally {
      clearToken();
    }
  },
  me() {
    return request('/auth/me');
  },
  completeOnboarding() {
    return request('/auth/onboarding', { method: 'PUT' });
  },

  // Farms
  getFarms() {
    return request('/farms');
  },
  getFarm(id) {
    return request(`/farms/${id}`);
  },
  createFarm(farm) {
    return request('/farms', { method: 'POST', body: JSON.stringify(farm) });
  },
  updateFarm(id, farm) {
    return request(`/farms/${id}`, { method: 'PUT', body: JSON.stringify(farm) });
  },
  deleteFarm(id) {
    return request(`/farms/${id}`, { method: 'DELETE' });
  },
  lookupLocation(lat, lon) {
    return request(`/geo/lookup?lat=${lat}&lon=${lon}`);
  },
  addSoilTest(farmId, data) {
    return request(`/farms/${farmId}/soil-tests`, { method: 'POST', body: JSON.stringify(data) });
  },
  addWaterTest(farmId, data) {
    return request(`/farms/${farmId}/water-tests`, { method: 'POST', body: JSON.stringify(data) });
  },

  // Groundwater
  getGroundwaterStations(filters = {}) {
    const p = new URLSearchParams();
    if (filters.county) p.append('county', filters.county);
    if (filters.salinity) p.append('salinity', filters.salinity);
    if (filters.basin) p.append('basin', filters.basin);
    return request(`/groundwater/stations?${p.toString()}`);
  },
  lookupGroundwater(lat, lon, county, elevation) {
    const p = new URLSearchParams({ lat: String(lat), lon: String(lon) });
    if (county) p.append('county', county);
    if (elevation) p.append('elevation', String(elevation));
    return request(`/groundwater/lookup?${p.toString()}`);
  },

  // Crops & suitability
  getCrops() {
    return request('/crops');
  },
  getFarmSuitability(farmId) {
    return request(`/farms/${farmId}/suitability`);
  },

  // Weather
  getFarmWeather(farmId) {
    return request(`/farms/${farmId}/weather`);
  },

  // Fertilizer
  getFertilizer(farmId, cropId) {
    const q = cropId ? `?crop_id=${cropId}` : '';
    return request(`/farms/${farmId}/fertilizer${q}`);
  },

  // Plans
  getPlans() {
    return request('/plans');
  },
  getPlanDetails(planId) {
    return request(`/plans/${planId}`);
  },
  generatePlan(payload) {
    return request('/plans/generate', { method: 'POST', body: JSON.stringify(payload) });
  },
  updateTaskStatus(taskId, status, notes) {
    return request(`/tasks/${taskId}`, { method: 'PUT', body: JSON.stringify({ status, notes }) });
  },

  // Pests
  getPestsAndAlerts() {
    return request('/pests');
  },
  diagnosePest(payload) {
    return request('/pests/diagnose', { method: 'POST', body: JSON.stringify(payload) });
  },

  // Markets & economics
  getMarketPrices() {
    return request('/markets');
  },
  importMarketPrices(prices) {
    return request('/markets/import', { method: 'POST', body: JSON.stringify(prices) });
  },
  simulateEconomics(payload) {
    return request('/economics/simulate', { method: 'POST', body: JSON.stringify(payload) });
  },

  // Specialists
  getSpecialists() {
    return request('/specialists');
  },
  submitConsultation(payload) {
    return request('/specialists/consult', { method: 'POST', body: JSON.stringify(payload) });
  },
};
