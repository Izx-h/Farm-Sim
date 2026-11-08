-- ============================================================================
-- AgriPulse — SQLite schema reference (documentation only)
-- The authoritative schema is defined by the Laravel migrations in ./migrations.
-- ============================================================================

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id                   TEXT PRIMARY KEY,
    name                 TEXT NOT NULL,
    email                TEXT NOT NULL UNIQUE,
    password_hash        TEXT NOT NULL,
    onboarding_completed INTEGER NOT NULL DEFAULT 0,
    created_at           DATETIME,
    updated_at           DATETIME
);

CREATE TABLE IF NOT EXISTS auth_tokens (
    id         TEXT PRIMARY KEY,
    user_id    TEXT NOT NULL,
    token_hash TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_auth_tokens_token_hash ON auth_tokens(token_hash);

CREATE TABLE IF NOT EXISTS farms (
    id                                TEXT PRIMARY KEY,
    user_id                           TEXT,
    name                              TEXT NOT NULL,
    owner                             TEXT NOT NULL,
    location_name                     TEXT NOT NULL,
    latitude                          REAL NOT NULL,
    longitude                         REAL NOT NULL,
    elevation_m                       REAL NOT NULL,
    slope_deg                         REAL NOT NULL,
    soil_type                         TEXT NOT NULL,
    soil_ph                           REAL NOT NULL,
    nitrogen_level                    TEXT NOT NULL,
    phosphorus_level                  TEXT NOT NULL,
    potassium_level                   TEXT NOT NULL,
    organic_matter_pct                REAL NOT NULL,
    drainage_class                    TEXT NOT NULL,
    water_source                      TEXT NOT NULL,
    area_acres                        REAL NOT NULL,
    farming_goal                      TEXT NOT NULL,
    groundwater_depth_m               REAL,
    groundwater_yield_m3_hr           REAL,
    groundwater_ec_ds_m               REAL,
    groundwater_tds_mg_l              REAL,
    groundwater_salinity_class        TEXT,
    groundwater_irrigation_suitability TEXT,
    aquifer_basin                     TEXT,
    notes                             TEXT,
    created_at                        DATETIME,
    updated_at                        DATETIME
);
CREATE INDEX IF NOT EXISTS idx_farms_user_id ON farms(user_id);

CREATE TABLE IF NOT EXISTS soil_tests (
    id                     TEXT PRIMARY KEY,
    farm_id                TEXT NOT NULL,
    test_date              DATE NOT NULL,
    laboratory             TEXT NOT NULL,
    ph                     REAL,
    n_ppm                  REAL,
    p_ppm                  REAL,
    k_ppm                  REAL,
    ec_ds_m                REAL,
    organic_carbon_pct     REAL,
    recommendation_summary TEXT,
    created_at             DATETIME,
    updated_at             DATETIME,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS water_tests (
    id                     TEXT PRIMARY KEY,
    farm_id                TEXT NOT NULL,
    test_date              DATE NOT NULL,
    laboratory             TEXT NOT NULL,
    source_type            TEXT NOT NULL,
    depth_m                REAL,
    static_water_level_m   REAL,
    yield_m3_hr            REAL,
    ph                     REAL,
    ec_ds_m                REAL,
    tds_mg_l               REAL,
    sar                    REAL,
    fluoride_mg_l          REAL,
    salinity_category      TEXT,
    irrigation_suitability TEXT,
    notes                  TEXT,
    created_at             DATETIME,
    updated_at             DATETIME,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS crops (
    id                          TEXT PRIMARY KEY,
    name                        TEXT NOT NULL,
    scientific_name             TEXT NOT NULL,
    category                    TEXT NOT NULL,
    min_ph                      REAL NOT NULL,
    max_ph                      REAL NOT NULL,
    optimal_ph_min              REAL NOT NULL,
    optimal_ph_max              REAL NOT NULL,
    min_elevation_m             REAL NOT NULL,
    max_elevation_m             REAL NOT NULL,
    min_temp_c                  REAL NOT NULL,
    max_temp_c                  REAL NOT NULL,
    optimal_temp_c              REAL NOT NULL,
    min_rainfall_mm             REAL NOT NULL,
    max_rainfall_mm             REAL NOT NULL,
    maturity_days               INTEGER NOT NULL,
    drought_tolerance           TEXT NOT NULL,
    flood_tolerance             TEXT NOT NULL,
    water_requirement_mm        INTEGER NOT NULL,
    average_yield_tons_per_acre REAL NOT NULL,
    market_price_per_kg         REAL NOT NULL,
    seed_cost_per_acre          REAL NOT NULL,
    fertilizer_cost_per_acre    REAL NOT NULL,
    labor_cost_per_acre         REAL NOT NULL,
    commercial_value_rating     INTEGER NOT NULL,
    food_security_rating        INTEGER NOT NULL,
    spacing_recommendation      TEXT NOT NULL,
    planting_depth_cm           REAL NOT NULL,
    description                 TEXT NOT NULL,
    variety                     TEXT NOT NULL DEFAULT '',
    planting_seasons            TEXT NOT NULL DEFAULT '',
    seed_rate                   TEXT NOT NULL DEFAULT '',
    nutrient_requirement        TEXT,
    major_pests                 TEXT,
    harvest_note                TEXT,
    salinity_tolerance_ec_ds_m  REAL NOT NULL DEFAULT 2.5,
    water_requirement_note      TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS crop_growth_stages (
    id                     TEXT PRIMARY KEY,
    crop_id                TEXT NOT NULL,
    stage_name             TEXT NOT NULL,
    stage_order            INTEGER NOT NULL,
    start_day              INTEGER NOT NULL,
    end_day                INTEGER NOT NULL,
    water_need_description TEXT NOT NULL,
    key_activities         TEXT NOT NULL,
    scout_priorities       TEXT NOT NULL,
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pest_diseases (
    id                   TEXT PRIMARY KEY,
    name                 TEXT NOT NULL,
    type                 TEXT NOT NULL,
    affected_crops       TEXT NOT NULL,
    symptoms             TEXT NOT NULL,
    favorable_conditions TEXT NOT NULL,
    severity             TEXT NOT NULL,
    prevention_measures  TEXT NOT NULL,
    chemical_control     TEXT NOT NULL,
    biological_control   TEXT NOT NULL,
    image_placeholder    TEXT
);

CREATE TABLE IF NOT EXISTS pest_alerts (
    id              TEXT PRIMARY KEY,
    region          TEXT NOT NULL,
    pest_disease_id TEXT NOT NULL,
    alert_level     TEXT NOT NULL,
    summary         TEXT NOT NULL,
    affected_crops  TEXT NOT NULL,
    action_required TEXT NOT NULL,
    reported_date   DATE NOT NULL,
    county          TEXT NOT NULL DEFAULT '',
    source_type     TEXT NOT NULL DEFAULT 'surveillance',
    FOREIGN KEY (pest_disease_id) REFERENCES pest_diseases(id)
);

CREATE TABLE IF NOT EXISTS farm_plans (
    id                     TEXT PRIMARY KEY,
    farm_id                TEXT NOT NULL,
    crop_id                TEXT NOT NULL,
    season_name            TEXT NOT NULL,
    target_planting_date   DATE NOT NULL,
    estimated_harvest_date DATE NOT NULL,
    planted_area_acres     REAL NOT NULL,
    status                 TEXT NOT NULL DEFAULT 'Active',
    projected_yield_tons   REAL NOT NULL,
    projected_revenue      REAL NOT NULL,
    estimated_total_cost   REAL NOT NULL,
    projected_net_margin   REAL NOT NULL,
    notes                  TEXT,
    variety                TEXT NOT NULL DEFAULT '',
    created_at             DATETIME,
    updated_at             DATETIME,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS plan_tasks (
    id             TEXT PRIMARY KEY,
    plan_id        TEXT NOT NULL,
    title          TEXT NOT NULL,
    category       TEXT NOT NULL,
    growth_stage   TEXT NOT NULL,
    due_day_offset INTEGER NOT NULL,
    status         TEXT NOT NULL DEFAULT 'Pending',
    notes          TEXT,
    completed_at   DATETIME,
    FOREIGN KEY (plan_id) REFERENCES farm_plans(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS market_prices (
    id                  TEXT PRIMARY KEY,
    crop_name           TEXT NOT NULL,
    market_name         TEXT NOT NULL,
    region              TEXT NOT NULL,
    unit                TEXT NOT NULL,
    wholesale_price_ksh REAL NOT NULL,
    retail_price_ksh    REAL NOT NULL,
    price_trend         TEXT NOT NULL,
    demand_level        TEXT NOT NULL,
    last_updated        DATE NOT NULL,
    source              TEXT NOT NULL DEFAULT 'reference',
    currency            TEXT NOT NULL DEFAULT 'KSh',
    is_live             INTEGER NOT NULL DEFAULT 0,
    source_url          TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS specialists (
    id                  TEXT PRIMARY KEY,
    name                TEXT NOT NULL,
    role                TEXT NOT NULL,
    organization        TEXT NOT NULL,
    specialization      TEXT NOT NULL,
    experience_years    INTEGER NOT NULL,
    email               TEXT NOT NULL,
    phone               TEXT NOT NULL,
    location            TEXT NOT NULL,
    rating              REAL NOT NULL,
    consultations_count INTEGER NOT NULL,
    verified            INTEGER NOT NULL DEFAULT 1,
    available           INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS consultations (
    id                  TEXT PRIMARY KEY,
    farm_id             TEXT NOT NULL,
    specialist_id       TEXT NOT NULL,
    farmer_name         TEXT NOT NULL,
    inquiry_type        TEXT NOT NULL,
    subject             TEXT NOT NULL,
    message             TEXT NOT NULL,
    status              TEXT NOT NULL DEFAULT 'Open',
    specialist_response TEXT,
    created_at          DATETIME,
    updated_at          DATETIME,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS groundwater_stations (
    id                     TEXT PRIMARY KEY,
    station_code           TEXT NOT NULL,
    name                   TEXT NOT NULL,
    county                 TEXT NOT NULL,
    sub_county             TEXT NOT NULL,
    locality               TEXT NOT NULL,
    latitude               REAL NOT NULL,
    longitude              REAL NOT NULL,
    aquifer_name           TEXT NOT NULL,
    basin_name             TEXT NOT NULL,
    depth_m                REAL NOT NULL,
    water_table_m          REAL NOT NULL,
    yield_m3_hr            REAL NOT NULL,
    ph                     REAL NOT NULL,
    ec_ds_m                REAL NOT NULL,
    tds_mg_l               REAL NOT NULL,
    sar                    REAL NOT NULL,
    fluoride_mg_l          REAL NOT NULL,
    chloride_mg_l          REAL NOT NULL,
    boron_mg_l             REAL NOT NULL,
    salinity_category      TEXT NOT NULL,
    irrigation_suitability TEXT NOT NULL,
    verified_source        TEXT NOT NULL,
    confidence_level       TEXT NOT NULL,
    last_tested_date       DATE NOT NULL
);
