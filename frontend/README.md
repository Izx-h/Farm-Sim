# Frontend — Blade + Vue 3 + Vite

The **frontend** is a Vue 3 single-page application rendered from one Blade template and built
with Vite. It is a recode of the original React/Vite client, using the same glassmorphic
agricultural design system.

```
frontend/
├── views/
│   └── app.blade.php        The single Blade shell that mounts the Vue app
├── js/
│   ├── app.js               Vue entry point (mounts App.vue)
│   ├── api.js               Fetch-based API client (bearer-token auth)
│   └── components/
│       ├── App.vue                        Root: auth/onboarding gates + tab shell
│       ├── AuthPage.vue                   Login / register
│       ├── OnboardingWizard.vue           Guided farm setup
│       ├── FarmMapSelector.vue            Leaflet pin-drop map
│       ├── CropSuitabilityView.vue        Crop scoring + recommendations
│       ├── FarmProfileView.vue            Farm details + lab tests
│       ├── GroundwaterIntelligenceView.vue Aquifer + station registry
│       ├── WeatherPlantingView.vue        7-day forecast + planting advisory
│       ├── CropPlanCalendarView.vue       Plans + task checklists
│       ├── PestDiseaseAlertsView.vue      Alerts + symptom checker
│       ├── CommercialEconomicsView.vue    Financial model + market board
│       └── SpecialistDirectoryView.vue    Specialists + consultations
├── css/
│   └── app.css              Design-system CSS (design tokens, glassmorphism)
├── package.json
├── vite.config.js
└── .env.example
```

## How it is wired

- `backend/config/view.php` points Laravel's view paths at `../frontend/views`, so the Blade
  shell in `views/app.blade.php` is found.
- `vite.config.js` uses the `laravel-vite-plugin` and builds into `../backend/public/build`,
  which is exactly where the `@vite` directive looks for the manifest.

## Setup

```powershell
npm install
```

## Develop

```powershell
npm run dev          # Vite dev server (hot reload), then open http://127.0.0.1:8000
```

Keep `php artisan serve` running in the `backend/` folder in a second terminal.

## Production build

```powershell
npm run build        # outputs to ../backend/public/build
```

After building, Laravel serves the compiled assets directly — no separate Vite process needed.

## API base

The app calls the Laravel API on the same origin (`/api`) by default. Set `VITE_API_BASE` in a
`frontend/.env` file only if you host the UI and API on different origins.
