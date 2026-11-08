<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialistSeeder extends Seeder
{
    public function run(): void
    {
        $specialists = [
            [
                'id' => 'sp-1',
                'name' => 'Dr. Beatrice Wanjiku, PhD',
                'role' => 'Senior Agronomist & Soil Chemist',
                'organization' => 'National Agricultural Research Organization (NARO)',
                'specialization' => 'Soil nutrient remediation, acid soil liming, and precision fertilizer scheduling',
                'experience_years' => 16,
                'email' => 'dr.beatrice@naro-agri.org',
                'phone' => '+254 712 345 678',
                'location' => 'Nakuru & Central Highlands',
                'rating' => 4.95,
                'consultations_count' => 284,
                'verified' => 1,
                'available' => 1,
            ],
            [
                'id' => 'sp-2',
                'name' => 'Eng. Peter K. Rotich',
                'role' => 'Irrigation & Water Resource Engineer',
                'organization' => 'HydroAgri Engineering Solutions',
                'specialization' => 'Drip system sizing, rainwater retention dams, borehole solar pumping',
                'experience_years' => 12,
                'email' => 'rotich@hydroagri.com',
                'phone' => '+254 723 889 012',
                'location' => 'Eldoret & Rift Valley',
                'rating' => 4.88,
                'consultations_count' => 176,
                'verified' => 1,
                'available' => 1,
            ],
            [
                'id' => 'sp-3',
                'name' => 'Dr. Mercy Akoth, MSc',
                'role' => 'Plant Pathologist & IPM Specialist',
                'organization' => 'CropGuard International Bio-Network',
                'specialization' => 'Fungal blights, viral vectors, biological parasitoids, fall armyworm control',
                'experience_years' => 11,
                'email' => 'mercy.akoth@cropguard.org',
                'phone' => '+254 734 556 789',
                'location' => 'Kisumu & Western Region',
                'rating' => 4.92,
                'consultations_count' => 210,
                'verified' => 1,
                'available' => 1,
            ],
            [
                'id' => 'sp-4',
                'name' => 'Martin Mwangi, CPA',
                'role' => 'Agricultural Economist & Farm Valuer',
                'organization' => 'AgriCapital Commercial Advisory',
                'specialization' => 'Production budgeting, cash crop ROI modeling, farm enterprise diversification',
                'experience_years' => 14,
                'email' => 'm.mwangi@agricapital.co',
                'phone' => '+254 722 411 900',
                'location' => 'Nairobi & Nationwide',
                'rating' => 4.85,
                'consultations_count' => 142,
                'verified' => 1,
                'available' => 1,
            ],
        ];

        DB::table('specialists')->upsert($specialists, ['id'], array_values(array_diff(array_keys($specialists[0]), ['id'])));
    }
}
