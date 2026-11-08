<?php

namespace Database\Seeders;

use App\Services\CropData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = app(CropData::class)->crops();

        $updateColumns = array_values(array_diff(array_keys($crops[0]), ['id']));
        DB::table('crops')->upsert($crops, ['id'], $updateColumns);

        if (DB::table('crop_growth_stages')->count() > 0) {
            return;
        }

        DB::table('crop_growth_stages')->insert($this->stages());
    }

    private function stages(): array
    {
        return [
            // Maize
            ['id' => 'st-m1', 'crop_id' => 'crop-maize', 'stage_name' => 'Land Preparation & Sowing', 'stage_order' => 1, 'start_day' => 0, 'end_day' => 10, 'water_need_description' => 'Moist soil bed required; avoid waterlogging', 'key_activities' => 'Deep plowing, basal DAP/NPK fertilizer application (50kg/acre), seed germination check', 'scout_priorities' => 'Cutworms and wireworms'],
            ['id' => 'st-m2', 'crop_id' => 'crop-maize', 'stage_name' => 'Vegetative & Early Canopy', 'stage_order' => 2, 'start_day' => 11, 'end_day' => 40, 'water_need_description' => 'Moderate (25-30mm/week); sensitive to moisture stress', 'key_activities' => 'First weeding at 2-3 weeks, top-dressing with CAN/Urea at knee-high (week 4-5)', 'scout_priorities' => 'Fall Armyworm (FAW) leaf funnel feeding, stem borers'],
            ['id' => 'st-m3', 'crop_id' => 'crop-maize', 'stage_name' => 'Tasseling & Silking (Critical)', 'stage_order' => 3, 'start_day' => 41, 'end_day' => 75, 'water_need_description' => 'Maximum water demand (45-55mm/week); moisture deficit cuts yield by 40%', 'key_activities' => 'Inspect pollination, second top-dressing if split, maintain weed-free buffer', 'scout_priorities' => 'Maize Lethal Necrosis (MLND) vectors, corn earworm'],
            ['id' => 'st-m4', 'crop_id' => 'crop-maize', 'stage_name' => 'Grain Filling & Maturation', 'stage_order' => 4, 'start_day' => 76, 'end_day' => 115, 'water_need_description' => 'Decreasing moisture requirement; sunny days ideal', 'key_activities' => 'Maintain security, check for cob rot and stalk lodging', 'scout_priorities' => 'Grey leaf spot, northern corn leaf blight, rodent control'],
            ['id' => 'st-m5', 'crop_id' => 'crop-maize', 'stage_name' => 'Ripening & Harvesting', 'stage_order' => 5, 'start_day' => 116, 'end_day' => 135, 'water_need_description' => 'Dry conditions needed to lower moisture below 13.5%', 'key_activities' => 'Harvest when black layer forms at grain tip, dry cobs, clean storage cribs', 'scout_priorities' => 'Weevils, grain borers, aflatoxin prevention'],

            // Beans
            ['id' => 'st-b1', 'crop_id' => 'crop-beans', 'stage_name' => 'Sowing & Germination', 'stage_order' => 1, 'start_day' => 0, 'end_day' => 8, 'water_need_description' => 'Steady gentle moisture', 'key_activities' => 'Inoculate with Rhizobium if available, apply single superphosphate', 'scout_priorities' => 'Bean fly seedling attacks'],
            ['id' => 'st-b2', 'crop_id' => 'crop-beans', 'stage_name' => 'Vegetative & Branching', 'stage_order' => 2, 'start_day' => 9, 'end_day' => 30, 'water_need_description' => 'Moderate moisture', 'key_activities' => 'Shallow hoe weeding to protect delicate surface roots', 'scout_priorities' => 'Aphids, whiteflies, bean leaf beetles'],
            ['id' => 'st-b3', 'crop_id' => 'crop-beans', 'stage_name' => 'Flowering & Pod Formation', 'stage_order' => 3, 'start_day' => 31, 'end_day' => 60, 'water_need_description' => 'Critical water sensitive phase; avoid spraying midday during bee activity', 'key_activities' => 'Foliar boron/zinc supplement if indicated, avoid overhead spray in damp weather', 'scout_priorities' => 'Anthracnose, rust, angular leaf spot, thrips'],
            ['id' => 'st-b4', 'crop_id' => 'crop-beans', 'stage_name' => 'Pod Maturation & Harvest', 'stage_order' => 4, 'start_day' => 61, 'end_day' => 85, 'water_need_description' => 'Dry conditions preferred', 'key_activities' => 'Pick dry pods or uproot early morning to reduce shattering; thresh and hermetic bag', 'scout_priorities' => 'Bruchid beetles, damp storage mold'],

            // Potatoes
            ['id' => 'st-p1', 'crop_id' => 'crop-potatoes', 'stage_name' => 'Sprouting & Tuber Planting', 'stage_order' => 1, 'start_day' => 0, 'end_day' => 15, 'water_need_description' => 'Moist furrow, high organic matter bed', 'key_activities' => 'Plant certified pre-sprouted seed tubers (50-60mm), apply compound NPK 17:17:17', 'scout_priorities' => 'Cutworms, seed-piece rot'],
            ['id' => 'st-p2', 'crop_id' => 'crop-potatoes', 'stage_name' => 'Vegetative & Hilling (Earthing up)', 'stage_order' => 2, 'start_day' => 16, 'end_day' => 45, 'water_need_description' => 'Consistent moisture to support rapid haulm growth', 'key_activities' => 'First earthing up to prevent greening of tubers and reduce tuber moth access', 'scout_priorities' => 'Potato tuber moth, aphids, early blight'],
            ['id' => 'st-p3', 'crop_id' => 'crop-potatoes', 'stage_name' => 'Tuber Initiation & Bulking', 'stage_order' => 3, 'start_day' => 46, 'end_day' => 85, 'water_need_description' => 'Peak water requirement; uneven watering causes hollow heart and knobby tubers', 'key_activities' => 'Second earthing up, preventive copper spray if rainy', 'scout_priorities' => 'Late Blight (Phytophthora infestans), bacterial wilt'],
            ['id' => 'st-p4', 'crop_id' => 'crop-potatoes', 'stage_name' => 'Haulm Senescence & Harvesting', 'stage_order' => 4, 'start_day' => 86, 'end_day' => 105, 'water_need_description' => 'Withhold irrigation 10-14 days prior to harvest to toughen skin', 'key_activities' => 'Dehaulm (cut foliage) 10 days before lifting to cure skins, dig carefully without bruising', 'scout_priorities' => 'Soft rot, post-harvest skin scuffing'],

            // Tomatoes
            ['id' => 'st-t1', 'crop_id' => 'crop-tomatoes', 'stage_name' => 'Nursery & Transplanting', 'stage_order' => 1, 'start_day' => 0, 'end_day' => 25, 'water_need_description' => 'High seedling moisture; harden seedlings 5 days before field transplant', 'key_activities' => 'Transplant sturdy 4-week seedlings in early morning/evening; water immediately with starter fertilizer', 'scout_priorities' => 'Damping off, cutworms'],
            ['id' => 'st-t2', 'crop_id' => 'crop-tomatoes', 'stage_name' => 'Vegetative & Staking', 'stage_order' => 2, 'start_day' => 26, 'end_day' => 50, 'water_need_description' => 'Regular drip irrigation (30mm/week)', 'key_activities' => 'Stake plants with sturdy bamboo/twine, prune lateral suckers, mulch base', 'scout_priorities' => 'Tuta absoluta leaf miners, whiteflies, bacterial canker'],
            ['id' => 'st-t3', 'crop_id' => 'crop-tomatoes', 'stage_name' => 'Flowering & Fruit Set', 'stage_order' => 3, 'start_day' => 51, 'end_day' => 70, 'water_need_description' => 'Critical consistent water; fluctuations trigger Blossom End Rot', 'key_activities' => 'Apply calcium nitrate foliar spray, inspect pollination and cluster health', 'scout_priorities' => 'Blossom end rot, tomato russet mites, Early and Late Blight'],
            ['id' => 'st-t4', 'crop_id' => 'crop-tomatoes', 'stage_name' => 'Fruit Ripening & Continuous Harvest', 'stage_order' => 4, 'start_day' => 71, 'end_day' => 100, 'water_need_description' => 'Moderate drip; avoid wet foliage', 'key_activities' => 'Harvest at breaker or pink stage for long transport, crate gently', 'scout_priorities' => 'Fruit borers, post-harvest fruit decay'],
        ];
    }
}
