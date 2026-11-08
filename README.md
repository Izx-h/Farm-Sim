# AgriPulse — Smart Agricultural Decision-Support Platform

An intelligent, data-driven agricultural decision-support system that helps subsistence and
commercial farmers make informed, proactive, and financially sound farming decisions. It
integrates seasonal weather forecasts, soil & terrain characteristics, groundwater intelligence,
crop requirements, pest/disease risk, and market intelligence to produce farm-specific
recommendations across the whole season.

This repository is a **Laravel + Blade + Vue + SQLite** recode of the original
Express/React prototype. It is organised into three clear layers:

```
Farm Sim/
├── backend/    Laravel application — models, controllers, services, routes, config
├── frontend/   Blade templates + Vue 3 components + Vite + the design-system CSS
└── database/   SQLite database file + migrations + seeders + a plain-SQL reference
```

| Layer      | Technology                     | Where it lives            |
| :--------- | :----------------------------- | :------------------------ |
| Frontend   | Blade templates + Vue 3 + Vite | `frontend/`               |
| Backend    | Laravel 12 (PHP 8.2+)          | `backend/`                |
| Database   | SQLite                         | `database/`               |

> **Note on deployment files.** The original prototype shipped `vercel.json`, Supabase and
> Render instructions. Those have been **removed** — this is a clean recode of the core system
> with no platform-specific lock-in.

---

## The three layers, mapped to Laravel

Laravel is a monolith, but this project deliberately splits it into three sibling folders. The
mapping is wired across three places:

- `frontend/`  → Blade views via `config/view.php` (`base_path('../frontend/views')`), with
  Vue/CSS compiled by Vite into `backend/public/build`.
- `database/`  → `bootstrap/app.php` calls `useDatabasePath()` so `database_path()` (migrations
  + seeders) points here, and `config/database.php` points the SQLite file here.

So a standard `php artisan migrate` and `@vite(...)` still work exactly as you would expect —
the files just live in the top-level `frontend/` and `database/` folders instead of inside the
app directory.

---

## Core system modules

1. **Land & Farm Profiling** — interactive map pin-drop, auto reverse-geocoding, elevation,
   slope, soil and groundwater auto-import, parcel management.
2. **Kenya-Wide Groundwater Intelligence** — 20 reference WRA/UNESCO borehole stations across
   all major Kenyan aquifers, salinity & sodicity diagnostics, fluoride toxicity guard.
3. **Multi-Factor Crop Suitability Engine** — 0–100 scoring of 31 crops against soil pH,
   altitude, slope, drainage, water and salinity, weighted by farming objective.
4. **Seasonal Forecasting & Planting Advisory** — live Open-Meteo 7-day forecast with a
   transparent, clearly-labelled planting-window heuristic (never a fabricated probability).
5. **Crop Management Plans & Activity Calendar** — growth-stage synchronised task checklists.
6. **Pest & Disease Early-Warning** — regional alerts + a probabilistic symptom checker.
7. **Commercial & Financial Decision Support** — acreage scaling, three gross-margin scenarios,
   break-even and ROI, plus a manual commodity price board (KSh).
8. **Specialist & Laboratory Directory** — agronomists, soil chemists, pathologists, irrigation
   engineers, with consultation requests.

---

## Quick start

### Prerequisites

- PHP **8.2+** with the `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl` and `curl` extensions.
- [Composer](https://getcomposer.org/)
- [Node.js 18+](https://nodejs.org/) and npm (for the Vite/Vue frontend).

### 1. Install the backend (Laravel)

```powershell
cd backend
composer install
Copy-Item .env.example .env       # or: copy .env.example .env
php artisan key:generate
```

### 2. Set up the database (SQLite)

The SQLite file lives at `../database/database.sqlite` (already created, empty). Then migrate and
seed:

```powershell
php artisan migrate --seed
```

This creates every table and seeds the agronomic reference data (31 crops, growth stages, 20
groundwater stations, pest/disease library + alerts, market reference prices, and specialists).

### 3. Install the frontend (Blade + Vue)

```powershell
cd ..\frontend
npm install
```

### 4. Run it

From two terminals:

```powershell
# Terminal 1 — Laravel API + Blade
cd backend
php artisan serve

# Terminal 2 — Vite dev server (Vue hot-reload)
cd frontend
npm run dev
```

Open **http://127.0.0.1:8000** — Laravel serves the Blade shell, and Vite supplies the compiled
Vue app. For a production build, run `npm run build` inside `frontend/` (outputs to
`backend/public/build`) and Laravel serves the built assets directly.

See `backend/README.md`, `frontend/README.md` and `database/README.md` for details.

---

## Environment variables

- `backend/.env.example` — Laravel app key, SQLite path, session TTL, Open-Meteo base URL.
- `frontend/.env.example` — `VITE_API_BASE` (only needed if you split UI and API across origins;
  defaults to same-origin `/api`).

No third-party API keys are required for any bundled integration (Open-Meteo and Nominatim are
public). Never commit real secrets.

---

## Data integrity & honesty

Every external value is labelled `estimated`, `verified`, `forecast` or `reference` in the UI.
Regional soil/groundwater values are **estimates** from a bundled agro-ecological model; only
user-entered laboratory values are treated as **verified**. Market prices are dated reference
data plus a manual import mechanism — never fabricated "live" prices. See `database/README.md`
for the full data-source register.
