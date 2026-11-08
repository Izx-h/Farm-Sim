# Database — SQLite

This folder is the **database layer** of AgriPulse. It holds the SQLite database file,
the Laravel migrations and seeders, and a plain-SQL reference of the schema.

```
database/
├── database.sqlite       Empty SQLite file (tables are created by `php artisan migrate`)
├── schema.sql            Plain-SQL reference of the schema (documentation only)
├── migrations/           Laravel migrations (one file per table)
└── seeders/              Reference-data seeders (crops, groundwater, pests, markets, specialists)
```

## How it is wired

- `backend/bootstrap/app.php` calls `useDatabasePath()` so Laravel's `database_path()` points
  here — `php artisan migrate` reads migrations from `database/migrations/`.
- `backend/config/database.php` points the SQLite file at `../database/database.sqlite`
  (leave `DB_DATABASE` unset in `.env`).
- `php artisan db:seed` resolves the `Database\Seeders\*` classes from `database/seeders/`
  via a small autoloader in `AppServiceProvider`.

## Quick start

```powershell
cd backend
php artisan migrate --seed
```

`--seed` loads the agronomic reference data:

| Seeder | Contents |
| :--- | :--- |
| `CropSeeder` | 31 crops + growth stages for maize, beans, potatoes, tomatoes |
| `GroundwaterStationSeeder` | 20 WRA/UNESCO reference borehole stations |
| `PestDiseaseSeeder` | 6 pest/disease records + 3 regional alerts |
| `MarketPriceSeeder` | 8 dated reference market prices |
| `SpecialistSeeder` | 4 agricultural specialists |

## Schema overview

| Table | Purpose |
| :--- | :--- |
| `users` | Farmer accounts (bcrypt `password_hash`) |
| `auth_tokens` | Opaque bearer-token sessions (SHA-256 digest stored) |
| `farms` | Farm/parcel profiles incl. inferred groundwater attributes |
| `soil_tests` | Laboratory soil-test records per farm |
| `water_tests` | Laboratory borehole/water-test records per farm |
| `crops` | Agronomic crop catalogue (pH, altitude, temp, rainfall, economics) |
| `crop_growth_stages` | Growth-stage scouting/activity guidance per crop |
| `pest_diseases` | Pest & disease reference knowledge base |
| `pest_alerts` | Regional early-warning alerts |
| `farm_plans` | Generated crop management plans |
| `plan_tasks` | Lifecycle task checklists under each plan |
| `market_prices` | Dated reference + manually-imported commodity prices |
| `specialists` | Agronomist / specialist directory |
| `consultations` | Specialist consultation requests |
| `groundwater_stations` | Reference borehole monitoring stations |

## Data integrity

- Soil/groundwater regional values are **estimates** from a bundled agro-ecological model.
- Only user-entered laboratory records (`soil_tests`, `water_tests`) are **verified**.
- Market prices are dated **reference** data plus a manual import mechanism — never live.
- Weather forecasts come live from Open-Meteo (no key required); the planting advisory is a
  transparent heuristic, never a fabricated probability.

See `schema.sql` for the exact column definitions.
