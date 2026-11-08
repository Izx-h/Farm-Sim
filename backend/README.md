# Backend — Laravel 12

The **backend** is a Laravel 12 application (PHP 8.2+) that exposes the AgriPulse JSON API
and serves the Blade/Vue single-page app. It is a faithful 1:1 recode of the original
Express + better-sqlite3 server.

```
backend/
├── app/
│   ├── Models/                 Eloquent models (User, Farm, Crop, FarmPlan, …)
│   ├── Http/
│   │   ├── Controllers/        API controllers (one per domain)
│   │   └── Middleware/         AuthenticateToken (bearer-token auth)
│   ├── Services/               Domain logic (decision engine, groundwater, geo, weather, crops)
│   └── Providers/              AppServiceProvider (maps frontend/ + database/ folders)
├── routes/
│   ├── api.php                 JSON API (mirrors the original endpoints)
│   └── web.php                 SPA fall-through → Blade view
├── config/                     Laravel config (app, database, session, …)
├── bootstrap/                  app.php + providers.php
├── public/                     Web root (index.php)
├── artisan                     CLI entry point
├── composer.json
└── .env / .env.example
```

## How the folders are wired

Three small changes keep the sibling `frontend/` and `database/` folders canonical:

```php
// bootstrap/app.php — migrations + seeders resolve from ../database
$app->useDatabasePath(dirname(__DIR__).'/../database');
```

```php
// config/view.php — Blade views resolve from ../frontend/views
'paths' => [base_path('../frontend/views')],
```

```php
// config/database.php — the SQLite file lives in ../database (Laravel 12 resolves
// a relative SQLite path against base_path(), not database_path())
'database' => env('DB_DATABASE', base_path('../database/database.sqlite')),
```

## API surface

Mirrors the original exactly (see `routes/api.php`):

| Area | Routes |
| :--- | :--- |
| Health | `GET /api/health` |
| Auth | `POST /api/auth/{register,login,logout}`, `GET /api/auth/me`, `PUT /api/auth/onboarding` |
| Geo | `GET /api/geo/lookup?lat=&lon=` |
| Groundwater | `GET /api/groundwater/{stations,lookup}` |
| Crops | `GET /api/crops`, `GET /api/crops/{id}`, `GET /api/farms/{id}/suitability` |
| Weather | `GET /api/farms/{id}/weather` |
| Fertilizer | `GET /api/farms/{id}/fertilizer` |
| Farms | `GET/POST /api/farms`, `GET/PUT/DELETE /api/farms/{id}`, `POST /api/farms/{id}/{soil-tests,water-tests}` |
| Plans | `GET /api/plans`, `GET /api/plans/{id}`, `POST /api/plans/generate`, `PUT /api/tasks/{id}` |
| Pests | `GET /api/pests`, `POST /api/pests/diagnose` |
| Markets | `GET /api/markets`, `POST /api/markets/import` |
| Economics | `POST /api/economics/simulate` |
| Specialists | `GET /api/specialists`, `POST /api/specialists/consult` |

## Authentication

Passwords are hashed with bcrypt (Laravel's `Hash`). Sessions use opaque bearer tokens; only a
SHA-256 digest is stored in `auth_tokens`. Tokens expire after `SESSION_TTL_HOURS` (default 168).

## Install & run

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Then start the Vite frontend (see `../frontend/README.md`) and open http://127.0.0.1:8000.

## Tests

```powershell
php artisan test
```

See `tests/Unit/DecisionEngineTest.php` and `tests/Unit/WeatherServiceTest.php`.
