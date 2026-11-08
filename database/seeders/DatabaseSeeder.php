<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's reference data.
     *
     * Note: these seeders live in the top-level `database/seeders` folder and
     * are autoloaded by AppServiceProvider (see backend/app/Providers).
     */
    public function run(): void
    {
        $this->call([
            CropSeeder::class,
            GroundwaterStationSeeder::class,
            PestDiseaseSeeder::class,
            MarketPriceSeeder::class,
            SpecialistSeeder::class,
        ]);
    }
}
