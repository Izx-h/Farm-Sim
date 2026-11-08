<?php

namespace Database\Seeders;

use App\Services\GroundwaterService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroundwaterStationSeeder extends Seeder
{
    public function run(): void
    {
        $stations = app(GroundwaterService::class)->stations();

        $updateColumns = array_values(array_diff(array_keys($stations[0]), ['id']));
        DB::table('groundwater_stations')->upsert($stations, ['id'], $updateColumns);
    }
}
