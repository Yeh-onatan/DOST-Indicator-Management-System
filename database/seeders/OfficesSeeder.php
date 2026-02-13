<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\PhilippineRegion;
use Illuminate\Database\Seeder;

class OfficesSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing offices
        Office::query()->delete();

        // 1. Get Regions for linking
        $regions = PhilippineRegion::all()->keyBy('code');

        // NOTE: DOST Central Office is now managed as an Agency (DOST-CO agency)
        // HQ staff should be assigned to the Central Office agency, not as office users

        // 2. Define ROs with a 'region_code' map to link them correctly
        $regionalOffices = [
            // Added NCR explicitly as it's often needed
            [
                'code' => 'DOST-NCR',
                'region_code' => 'NCR',
                'name' => 'DOST National Capital Region',
                'pstos' => [
                    ['code' => 'DOST-PSTO-MUNTAPARLAS', 'name' => 'DOST PSTO MUNTAPARLAS'],
                    ['code' => 'DOST-PSTO-PAMAMAZON', 'name' => 'DOST PSTO PAMAMAZON'],
                    ['code' => 'DOST-PSTO-CAMANAVA', 'name' => 'DOST PSTO CAMANAVA'],
                    ['code' => 'DOST-PSTO-QUEZON-CITY', 'name' => 'DOST PSTO Quezon City'],
                    ['code' => 'DOST-PSTO-MANILA', 'name' => 'DOST PSTO Manila'],
                ],
            ],
            [
                'code' => 'DOST-RO-I', 
                'region_code' => 'R1',
                'name' => 'DOST Regional Office I',
                'pstos' => [
                    ['code' => 'DOST-PSTO-ILOCOS-NORTE', 'name' => 'DOST PSTO Ilocos Norte'],
                    ['code' => 'DOST-PSTO-ILOCOS-SUR', 'name' => 'DOST PSTO Ilocos Sur'],
                    ['code' => 'DOST-PSTO-LA-UNION', 'name' => 'DOST PSTO La Union'],
                    ['code' => 'DOST-PSTO-PANGASINAN', 'name' => 'DOST PSTO Pangasinan'],
                ],
            ],
            [
                'code' => 'DOST-RO-II',
                'region_code' => 'R2',
                'name' => 'DOST Regional Office II',
                'pstos' => [
                    ['code' => 'DOST-PSTO-BATANES', 'name' => 'DOST PSTO Batanes'],
                    ['code' => 'DOST-PSTO-CAGAYAN', 'name' => 'DOST PSTO Cagayan'],
                    ['code' => 'DOST-PSTO-ISABELA', 'name' => 'DOST PSTO Isabela'],
                    ['code' => 'DOST-PSTO-NUEVA-VIZCAYA', 'name' => 'DOST PSTO Nueva Vizcaya'],
                    ['code' => 'DOST-PSTO-QUIRINO', 'name' => 'DOST PSTO Quirino'],
                ],
            ],
            [
                'code' => 'DOST-RO-III',
                'region_code' => 'R3',
                'name' => 'DOST Regional Office III',
                'pstos' => [
                    ['code' => 'DOST-PSTO-AURORA', 'name' => 'DOST PSTO Aurora'],
                    ['code' => 'DOST-PSTO-BATAAN', 'name' => 'DOST PSTO Bataan'],
                    ['code' => 'DOST-PSTO-BULACAN', 'name' => 'DOST PSTO Bulacan'],
                    ['code' => 'DOST-PSTO-NUEVA-ECIJA', 'name' => 'DOST PSTO Nueva Ecija'],
                    ['code' => 'DOST-PSTO-PAMPANGA', 'name' => 'DOST PSTO Pampanga'],
                    ['code' => 'DOST-PSTO-TARLAC', 'name' => 'DOST PSTO Tarlac'],
                    ['code' => 'DOST-PSTO-ZAMBALES', 'name' => 'DOST PSTO Zambales'],
                ],
            ],
            [
                'code' => 'DOST-RO-IVA',
                'region_code' => 'R4A',
                'name' => 'DOST Regional Office IV-A',
                'pstos' => [
                    ['code' => 'DOST-PSTO-CAVITE', 'name' => 'DOST PSTO Cavite'],
                    ['code' => 'DOST-PSTO-LAGUNA', 'name' => 'DOST PSTO Laguna'],
                    ['code' => 'DOST-PSTO-BATANGAS', 'name' => 'DOST PSTO Batangas'],
                    ['code' => 'DOST-PSTO-RIZAL', 'name' => 'DOST PSTO Rizal'],
                    ['code' => 'DOST-PSTO-QUEZON', 'name' => 'DOST PSTO Quezon'],
                ],
            ],
            [
                'code' => 'DOST-RO-IVB',
                'region_code' => 'R4B',
                'name' => 'DOST Regional Office IV-B',
                'pstos' => [
                    ['code' => 'DOST-PSTO-OCCIDENTAL-MINDORO', 'name' => 'DOST PSTO Occidental Mindoro'],
                    ['code' => 'DOST-PSTO-ORIENTAL-MINDORO', 'name' => 'DOST PSTO Oriental Mindoro'],
                    ['code' => 'DOST-PSTO-MARINDUQUE', 'name' => 'DOST PSTO Marinduque'],
                    ['code' => 'DOST-PSTO-ROMBLON', 'name' => 'DOST PSTO Romblon'],
                    ['code' => 'DOST-PSTO-PALAWAN', 'name' => 'DOST PSTO Palawan'],
                ],
            ],
            [
                'code' => 'DOST-RO-V',
                'region_code' => 'R5',
                'name' => 'DOST Regional Office V',
                'pstos' => [
                    ['code' => 'DOST-PSTO-ALBAY', 'name' => 'DOST PSTO Albay'],
                    ['code' => 'DOST-PSTO-CAMARINES-NORTE', 'name' => 'DOST PSTO Camarines Norte'],
                    ['code' => 'DOST-PSTO-CAMARINES-SUR', 'name' => 'DOST PSTO Camarines Sur'],
                    ['code' => 'DOST-PSTO-CATANDUANES', 'name' => 'DOST PSTO Catanduanes'],
                    ['code' => 'DOST-PSTO-MASBATE', 'name' => 'DOST PSTO Masbate'],
                    ['code' => 'DOST-PSTO-SORSOGON', 'name' => 'DOST PSTO Sorsogon'],
                ],
            ],
            [
                'code' => 'DOST-RO-VI',
                'region_code' => 'R6',
                'name' => 'DOST Regional Office VI',
                'pstos' => [
                    ['code' => 'DOST-PSTO-AKLAN', 'name' => 'DOST PSTO Aklan'],
                    ['code' => 'DOST-PSTO-ANTIQUE', 'name' => 'DOST PSTO Antique'],
                    ['code' => 'DOST-PSTO-CAPIZ', 'name' => 'DOST PSTO Capiz'],
                    ['code' => 'DOST-PSTO-GUIMARAS', 'name' => 'DOST PSTO Guimaras'],
                    ['code' => 'DOST-PSTO-ILOILO', 'name' => 'DOST PSTO Iloilo'],
                    ['code' => 'DOST-PSTO-NEGROS-OCCIDENTAL', 'name' => 'DOST PSTO Negros Occidental'],
                ],
            ],
            [
                'code' => 'DOST-RO-VII',
                'region_code' => 'R7',
                'name' => 'DOST Regional Office VII',
                'pstos' => [
                    ['code' => 'DOST-PSTO-BOHOL', 'name' => 'DOST PSTO Bohol'],
                    ['code' => 'DOST-PSTO-CEBU', 'name' => 'DOST PSTO Cebu'],
                    ['code' => 'DOST-PSTO-NEGROS-ORIENTAL', 'name' => 'DOST PSTO Negros Oriental'],
                    ['code' => 'DOST-PSTO-SIQUIJOR', 'name' => 'DOST PSTO Siquijor'],
                ],
            ],
            [
                'code' => 'DOST-RO-VIII',
                'region_code' => 'R8',
                'name' => 'DOST Regional Office VIII',
                'pstos' => [
                    ['code' => 'DOST-PSTO-LEYTE', 'name' => 'DOST PSTO Leyte'],
                    ['code' => 'DOST-PSTO-SOUTHERN-LEYTE', 'name' => 'DOST PSTO Southern Leyte'],
                    ['code' => 'DOST-PSTO-EASTERN-SAMAR', 'name' => 'DOST PSTO Eastern Samar'],
                    ['code' => 'DOST-PSTO-NORTHERN-SAMAR', 'name' => 'DOST PSTO Northern Samar'],
                    ['code' => 'DOST-PSTO-WESTERN-SAMAR', 'name' => 'DOST PSTO Western Samar'],
                ],
            ],
            [
                'code' => 'DOST-RO-IX',
                'region_code' => 'R9',
                'name' => 'DOST Regional Office IX',
                'pstos' => [
                    ['code' => 'DOST-PSTO-ZAMBOANGA-DEL-NORTE', 'name' => 'DOST PSTO Zamboanga del Norte'],
                    ['code' => 'DOST-PSTO-ZAMBOANGA-DEL-SUR', 'name' => 'DOST PSTO Zamboanga del Sur'],
                    ['code' => 'DOST-PSTO-ZAMBOANGA-SIBUGAY', 'name' => 'DOST PSTO Zamboanga Sibugay'],
                ],
            ],
            [
                'code' => 'DOST-RO-X',
                'region_code' => 'R10',
                'name' => 'DOST Regional Office X',
                'pstos' => [
                    ['code' => 'DOST-PSTO-BUKIDNON', 'name' => 'DOST PSTO Bukidnon'],
                    ['code' => 'DOST-PSTO-CAMIGUIN', 'name' => 'DOST PSTO Camiguin'],
                    ['code' => 'DOST-PSTO-LANAO-DEL-NORTE', 'name' => 'DOST PSTO Lanao del Norte'],
                    ['code' => 'DOST-PSTO-MISAMIS-OCCIDENTAL', 'name' => 'DOST PSTO Misamis Occidental'],
                    ['code' => 'DOST-PSTO-MISAMIS-ORIENTAL', 'name' => 'DOST PSTO Misamis Oriental'],
                ],
            ],
            [
                'code' => 'DOST-RO-XI',
                'region_code' => 'R11',
                'name' => 'DOST Regional Office XI',
                'pstos' => [
                    ['code' => 'DOST-PSTO-DAVAO-DE-ORO', 'name' => 'DOST PSTO Davao de Oro'],
                    ['code' => 'DOST-PSTO-DAVAO-DEL-NORTE', 'name' => 'DOST PSTO Davao del Norte'],
                    ['code' => 'DOST-PSTO-DAVAO-DEL-SUR', 'name' => 'DOST PSTO Davao del Sur'],
                    ['code' => 'DOST-PSTO-DAVAO-OCCIDENTAL', 'name' => 'DOST PSTO Davao Occidental'],
                    ['code' => 'DOST-PSTO-DAVAO-ORIENTAL', 'name' => 'DOST PSTO Davao Oriental'],
                ],
            ],
            [
                'code' => 'DOST-RO-XII',
                'region_code' => 'R12',
                'name' => 'DOST Regional Office XII',
                'pstos' => [
                    ['code' => 'DOST-PSTO-COTABATO', 'name' => 'DOST PSTO Cotabato'],
                    ['code' => 'DOST-PSTO-SOUTH-COTABATO', 'name' => 'DOST PSTO South Cotabato'],
                    ['code' => 'DOST-PSTO-SULTAN-KUDARAT', 'name' => 'DOST PSTO Sultan Kudarat'],
                    ['code' => 'DOST-PSTO-SARANGANI', 'name' => 'DOST PSTO Sarangani'],
                ],
            ],
            [
                'code' => 'DOST-RO-XIII',
                'region_code' => 'R13',
                'name' => 'DOST Regional Office XIII',
                'pstos' => [
                    ['code' => 'DOST-PSTO-AGUSAN-DEL-NORTE', 'name' => 'DOST PSTO Agusan del Norte'],
                    ['code' => 'DOST-PSTO-AGUSAN-DEL-SUR', 'name' => 'DOST PSTO Agusan del Sur'],
                    ['code' => 'DOST-PSTO-SURIGAO-DEL-NORTE', 'name' => 'DOST PSTO Surigao del Norte'],
                    ['code' => 'DOST-PSTO-SURIGAO-DEL-SUR', 'name' => 'DOST PSTO Surigao del Sur'],
                    ['code' => 'DOST-PSTO-DINAGAT-ISLANDS', 'name' => 'DOST PSTO Dinagat Islands'],
                ],
            ],
            [
                'code' => 'DOST-CAR',
                'region_code' => 'CAR',
                'name' => 'DOST Cordillera Administrative Region',
                'pstos' => [
                    ['code' => 'DOST-PSTO-ABRA', 'name' => 'DOST PSTO Abra'],
                    ['code' => 'DOST-PSTO-APAYAO', 'name' => 'DOST PSTO Apayao'],
                    ['code' => 'DOST-PSTO-BENGUET', 'name' => 'DOST PSTO Benguet'],
                    ['code' => 'DOST-PSTO-IFUGAO', 'name' => 'DOST PSTO Ifugao'],
                    ['code' => 'DOST-PSTO-KALINGA', 'name' => 'DOST PSTO Kalinga'],
                    ['code' => 'DOST-PSTO-MOUNTAIN-PROVINCE', 'name' => 'DOST PSTO Mountain Province'],
                ],
            ],
            [
                'code' => 'DOST-BARMM',
                'region_code' => 'BARMM',
                'name' => 'DOST Bangsamoro Autonomous Region',
                'pstos' => [
                    ['code' => 'DOST-PSTO-BASILAN', 'name' => 'DOST PSTO Basilan'],
                    ['code' => 'DOST-PSTO-LANAO-DEL-SUR', 'name' => 'DOST PSTO Lanao del Sur'],
                    ['code' => 'DOST-PSTO-MAGUINDANAO', 'name' => 'DOST PSTO Maguindanao'],
                    ['code' => 'DOST-PSTO-SULU', 'name' => 'DOST PSTO Sulu'],
                    ['code' => 'DOST-PSTO-TAWI-TAWI', 'name' => 'DOST PSTO Tawi-Tawi'],
                ],
            ],
        ];

        // Create all ROs and their PSTOs
        foreach ($regionalOffices as $roData) {
            // Find the Region ID to create the link
            $regionId = $regions[$roData['region_code']]->id ?? null;

            $ro = Office::create([
                'code' => $roData['code'],
                'name' => $roData['name'],
                'parent_office_id' => null,  // RO offices have no parent (Central Office is now an agency)
                'type' => 'RO',
                'region_id' => $regionId, // Linked to Regions table
            ]);

            // Create PSTOs under this RO
            foreach ($roData['pstos'] as $pstoData) {
                Office::create([
                    'code' => $pstoData['code'],
                    'name' => $pstoData['name'],
                    'parent_office_id' => $ro->id,
                    'type' => 'PSTO',
                    'region_id' => $regionId, // PSTO inherits the Region ID
                ]);
            }
        }

        $this->command->info('Offices seeded successfully!');
        $this->command->info('- ' . count($regionalOffices) . ' Regional Offices');
        $this->command->info('- ' . Office::where('type', 'PSTO')->count() . ' Provincial S&T Offices');
    }
}