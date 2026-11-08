<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketPriceSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            ['id' => 'mp-1', 'crop_name' => 'Hybrid Maize (Corn)', 'market_name' => 'Wakulima Central Market', 'region' => 'Nairobi Metropolis', 'unit' => '90kg bag', 'wholesale_price_ksh' => 4600, 'retail_price_ksh' => 5300, 'price_trend' => 'Rising', 'demand_level' => 'High', 'last_updated' => '2026-10-15'],
            ['id' => 'mp-2', 'crop_name' => 'Hybrid Maize (Corn)', 'market_name' => 'Nakuru Wholesale Hub', 'region' => 'Rift Valley', 'unit' => '90kg bag', 'wholesale_price_ksh' => 4200, 'retail_price_ksh' => 4800, 'price_trend' => 'Stable', 'demand_level' => 'Moderate', 'last_updated' => '2026-10-16'],
            ['id' => 'mp-3', 'crop_name' => 'Common Bush Beans', 'market_name' => 'Kibuye Open Market', 'region' => 'Lake Basin', 'unit' => '90kg bag', 'wholesale_price_ksh' => 12000, 'retail_price_ksh' => 13800, 'price_trend' => 'Rising', 'demand_level' => 'High', 'last_updated' => '2026-10-15'],
            ['id' => 'mp-4', 'crop_name' => 'Irish Potatoes (Shangi)', 'market_name' => 'Marikiti Wholesale Hub', 'region' => 'Nairobi Metropolis', 'unit' => '50kg bag', 'wholesale_price_ksh' => 3200, 'retail_price_ksh' => 3900, 'price_trend' => 'Falling', 'demand_level' => 'High', 'last_updated' => '2026-10-16'],
            ['id' => 'mp-5', 'crop_name' => 'Hybrid Tomato', 'market_name' => 'Gikomba Market', 'region' => 'Nairobi Metropolis', 'unit' => '60kg crate', 'wholesale_price_ksh' => 6200, 'retail_price_ksh' => 7500, 'price_trend' => 'Rising', 'demand_level' => 'High', 'last_updated' => '2026-10-16'],
            ['id' => 'mp-6', 'crop_name' => 'Red Bulb Onions', 'market_name' => 'Kongowea Central Market', 'region' => 'Coastal / Mombasa', 'unit' => '50kg bag', 'wholesale_price_ksh' => 5400, 'retail_price_ksh' => 6600, 'price_trend' => 'Stable', 'demand_level' => 'High', 'last_updated' => '2026-10-14'],
            ['id' => 'mp-7', 'crop_name' => 'Sweet / Grain Sorghum', 'market_name' => 'Eldoret Grain Terminal', 'region' => 'North Rift', 'unit' => '90kg bag', 'wholesale_price_ksh' => 4600, 'retail_price_ksh' => 5400, 'price_trend' => 'Rising', 'demand_level' => 'High', 'last_updated' => '2026-10-13'],
            ['id' => 'mp-8', 'crop_name' => 'Hass Avocado (Export Grade)', 'market_name' => 'Murang’a Packhouse Hub', 'region' => 'Central Region', 'unit' => '4kg carton (16-18 count)', 'wholesale_price_ksh' => 850, 'retail_price_ksh' => 1100, 'price_trend' => 'Rising', 'demand_level' => 'High', 'last_updated' => '2026-10-16'],
        ];

        foreach ($prices as &$p) {
            $p += ['source' => 'reference', 'currency' => 'KSh', 'is_live' => 0, 'source_url' => ''];
        }
        unset($p);

        DB::table('market_prices')->upsert($prices, ['id'], array_values(array_diff(array_keys($prices[0]), ['id'])));
    }
}
