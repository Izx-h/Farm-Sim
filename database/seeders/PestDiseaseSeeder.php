<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PestDiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $pests = [
            [
                'id' => 'pest-1',
                'name' => 'Fall Armyworm (Spodoptera frugiperda)',
                'type' => 'Pest',
                'affected_crops' => 'Maize, Sorghum, Cereal',
                'symptoms' => 'Extensive ragged windowpane holes on young leaves, heavy sawdust-like frass inside the plant whorl, severed cobs.',
                'favorable_conditions' => 'Warm temperatures (24-30°C), intermittent dry spells followed by light showers.',
                'severity' => 'Severe',
                'prevention_measures' => 'Early planting with rainfall onset, intercropping with silverleaf desmodium (push-pull strategy), frequent field scouting.',
                'chemical_control' => 'Emamectin benzoate 5% SG, Chlorantraniliprole, Spinetoram directed straight into the funnel.',
                'biological_control' => 'Bacillus thuringiensis (Bt) sprays, Trichogramma egg parasitoids, neem extract oil.',
                'image_placeholder' => 'pest_fall_armyworm.jpg',
            ],
            [
                'id' => 'pest-2',
                'name' => 'Late Blight (Phytophthora infestans)',
                'type' => 'Fungal Disease',
                'affected_crops' => 'Irish Potatoes, Tomatoes',
                'symptoms' => 'Water-soaked pale to brown lesions on leaf edges rapidly expanding, white cottony fungal growth on leaf undersides in morning dampness, rotting tubers.',
                'favorable_conditions' => 'Cool temperatures (15-22°C), high relative humidity (>90%), continuous rain or dense fog.',
                'severity' => 'Severe',
                'prevention_measures' => 'Plant certified disease-free seed, wider plant spacing to enhance airflow, avoid overhead sprinkler watering.',
                'chemical_control' => 'Preventive Mancozeb / Chlorothalonil before rain, curative systemic Metalaxyl + Mancozeb upon first lesion detection.',
                'biological_control' => 'Copper oxychloride organic barrier sprays, Trichoderma harzianum soil inoculation.',
                'image_placeholder' => 'pest_late_blight.jpg',
            ],
            [
                'id' => 'pest-3',
                'name' => 'Tuta Absoluta (Tomato Leafminer)',
                'type' => 'Pest',
                'affected_crops' => 'Tomatoes, Irish Potatoes',
                'symptoms' => 'Silvery transparent blotch mines inside leaf parenchyma, bore holes beneath calyx of fruit causing rapid decay.',
                'favorable_conditions' => 'Warm dry weather, poor greenhouse ventilation, continuous solanaceous monoculture.',
                'severity' => 'High',
                'prevention_measures' => 'Pheromone delta traps for mass trapping and monitoring, insect-proof netting (40 mesh), removal of wild nightshade weeds.',
                'chemical_control' => 'Flubendiamide, Spinosad, Indoxacarb rotated every 10 days to forestall insecticide resistance.',
                'biological_control' => 'Nesidiocoris tenuis predatory mirid bugs, neem Azadirachtin applications.',
                'image_placeholder' => 'pest_tuta_absoluta.jpg',
            ],
            [
                'id' => 'pest-4',
                'name' => 'Maize Lethal Necrosis Disease (MLND)',
                'type' => 'Viral Disease',
                'affected_crops' => 'Hybrid Maize',
                'symptoms' => 'Mottling and chlorosis along leaf veins, premature drying and bleaching of leaves from margins inward, barren stunted ears.',
                'favorable_conditions' => 'High populations of vector insects (maize thrips, corn flea beetles, chrysomelids), year-round overlapping maize planting.',
                'severity' => 'Severe',
                'prevention_measures' => 'Implement a strict maize-free break season, plant certified tolerant varieties, avoid planting downwind of infected fields.',
                'chemical_control' => 'Vector knock-down: Acetamiprid + Cypermethrin or Imidacloprid seed dressing.',
                'biological_control' => 'Bio-pesticides targeting thrips populations, crop rotation with non-host legumes (beans, cowpeas).',
                'image_placeholder' => 'pest_mlnd.jpg',
            ],
            [
                'id' => 'pest-5',
                'name' => 'Bean Anthracnose (Colletotrichum lindemuthianum)',
                'type' => 'Fungal Disease',
                'affected_crops' => 'Common Bush Beans, Soybeans',
                'symptoms' => 'Dark reddish-brown to black sunken lesions along leaf veins on undersides, circular sunken lesions on pods with salmon-pink spore masses.',
                'favorable_conditions' => 'Moderate temperatures (18-24°C) with persistent rainy overcast weather and high humidity.',
                'severity' => 'Moderate',
                'prevention_measures' => 'Certified disease-free seeds, minimum 2-year crop rotation without legumes, avoid walking through wet bean fields.',
                'chemical_control' => 'Azoxystrobin, Carbendazim + Mancozeb sprays at early flowering.',
                'biological_control' => 'Seed dressing with Trichoderma viride and hot-water seed soaking (50°C for 15 mins).',
                'image_placeholder' => 'pest_anthracnose.jpg',
            ],
            [
                'id' => 'pest-6',
                'name' => 'Aphids & Whiteflies (Vector Complex)',
                'type' => 'Pest',
                'affected_crops' => 'Vegetables, Legumes, Tuber, Cash Crop',
                'symptoms' => 'Curled leaf margins, sticky honeydew attracting black sooty mold, vectoring mosaic viruses and yellow leaf curl.',
                'favorable_conditions' => 'Dry weather with moderate warmth, excessive nitrogen fertilization causing lush succulent foliage.',
                'severity' => 'Moderate',
                'prevention_measures' => 'Yellow sticky sheets (10-15 per acre), reflective silver plastic mulch, companion planting with marigolds.',
                'chemical_control' => 'Acetamiprid, Thiamethoxam, Flonicamid.',
                'biological_control' => 'Coccinellid ladybird beetles, Chrysoperla lacewing larvae, horticultural potassium salt soap sprays.',
                'image_placeholder' => 'pest_aphids.jpg',
            ],
        ];

        $alerts = [
            [
                'id' => 'alt-1', 'region' => 'Rift Valley Highlands, Nakuru', 'pest_disease_id' => 'pest-1', 'alert_level' => 'Warning',
                'summary' => 'Recent intermittent showers followed by warm sunny afternoons have accelerated Fall Armyworm egg hatch across young maize crops.',
                'affected_crops' => 'Maize, Sorghum',
                'action_required' => 'Inspect 20 random plants per acre in a W-pattern. If >10% show fresh pin-hole feeding or frass, apply targeted whorl insecticide immediately before larvae penetrate stem.',
                'reported_date' => '2026-10-14', 'county' => 'Nakuru', 'source_type' => 'surveillance',
            ],
            [
                'id' => 'alt-2', 'region' => 'Central Slopes, Nyeri', 'pest_disease_id' => 'pest-2', 'alert_level' => 'Emergency',
                'summary' => 'Prolonged evening mist and temperatures dropping to 14°C have triggered widespread Late Blight spore sporulation in potato fields.',
                'affected_crops' => 'Irish Potatoes, Tomatoes',
                'action_required' => 'Spray protective systemic fungicide (Metalaxyl + Mancozeb) without delay. Ensure complete coverage of both upper and lower leaf surfaces.',
                'reported_date' => '2026-10-15', 'county' => 'Nyeri', 'source_type' => 'surveillance',
            ],
            [
                'id' => 'alt-3', 'region' => 'Lake Basin Region, Kisumu', 'pest_disease_id' => 'pest-6', 'alert_level' => 'Watch',
                'summary' => 'Rising aphid populations observed on legume plots with signs of bean common mosaic virus transmission.',
                'affected_crops' => 'Beans, Cowpeas, Vegetables',
                'action_required' => 'Erect yellow sticky cards and spray neem oil or bio-soap before aphid colonies establish within curled leaves.',
                'reported_date' => '2026-10-12', 'county' => 'Kisumu', 'source_type' => 'surveillance',
            ],
        ];

        if (DB::table('pest_diseases')->count() === 0) {
            DB::table('pest_diseases')->insert($pests);
        }

        if (DB::table('pest_alerts')->count() === 0) {
            DB::table('pest_alerts')->insert($alerts);
        }
    }
}
