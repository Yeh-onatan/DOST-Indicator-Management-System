<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhilippineRegion;

class RegionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            ['code' => 'NCR',    'name' => 'National Capital Region',              'order_index' => 1],
            ['code' => 'CAR',    'name' => 'Cordillera Administrative Region',      'order_index' => 2],
            ['code' => 'R1',     'name' => 'Region I (Ilocos Region)',             'order_index' => 3],
            ['code' => 'R2',     'name' => 'Region II (Cagayan Valley)',           'order_index' => 4],
            ['code' => 'R3',     'name' => 'Region III (Central Luzon)',           'order_index' => 5],
            ['code' => 'R4A',    'name' => 'CALABARZON',                           'order_index' => 6],
            ['code' => 'R4B',    'name' => 'MIMAROPA',                             'order_index' => 7],
            ['code' => 'R5',     'name' => 'Region V (Bicol Region)',              'order_index' => 8],
            ['code' => 'R6',     'name' => 'Region VI (Western Visayas)',          'order_index' => 9],
            ['code' => 'R7',     'name' => 'Region VII (Central Visayas)',         'order_index' => 10],
            ['code' => 'R8',     'name' => 'Region VIII (Eastern Visayas)',        'order_index' => 11],
            ['code' => 'R9',     'name' => 'Region IX (Zamboanga Peninsula)',      'order_index' => 12],
            ['code' => 'R10',    'name' => 'Region X (Northern Mindanao)',         'order_index' => 13],
            ['code' => 'R11',    'name' => 'Region XI (Davao Region)',             'order_index' => 14],
            ['code' => 'R12',    'name' => 'Region XII (SOCCSKSARGEN)',            'order_index' => 15],
            ['code' => 'CARAGA', 'name' => 'Caraga Region',                        'order_index' => 16],
            ['code' => 'BARMM',  'name' => 'Bangsamoro Autonomous Region',         'order_index' => 17],
        ];

        foreach ($regions as $region) {
            PhilippineRegion::updateOrCreate(
                ['code' => $region['code']], // The unique identifier to check
                [
                    'name' => $region['name'],
                    'order_index' => $region['order_index']
                ]
            );
        }
    }
}