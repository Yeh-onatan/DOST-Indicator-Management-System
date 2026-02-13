<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DOSTAgency;
use Illuminate\Support\Facades\DB;

class AgenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agencies = [
            // Main DOST Office
            [
                'code' => 'DOST',
                'name' => 'Department of Science and Technology',
                'acronym' => 'DOST',
                'cluster' => 'main',
                'description' => 'Main DOST Office',
                'is_active' => true,
            ],

            // DOST Central Office (HQ Staff)
            [
                'code' => 'DOST-CO',
                'name' => 'DOST Central Office',
                'acronym' => 'DOST-CO',
                'cluster' => 'ssi',
                'description' => 'DOST Central Office - Headquarters staff and programs',
                'is_active' => true,
            ],

            // Sectoral Planning Councils
            [
                'code' => 'PCAARRD',
                'name' => 'Philippine Council for Agriculture, Aquatic and Natural Resources Research and Development',
                'acronym' => 'PCAARRD',
                'cluster' => 'council',
                'description' => 'Sectoral Planning Council',
                'is_active' => true,
            ],
            [
                'code' => 'PCHRD',
                'name' => 'Philippine Council for Health Research and Development',
                'acronym' => 'PCHRD',
                'cluster' => 'council',
                'description' => 'Sectoral Planning Council',
                'is_active' => true,
            ],
            [
                'code' => 'PCIEERD',
                'name' => 'Philippine Council for Industry, Energy and Emerging Technology Research and Development',
                'acronym' => 'PCIEERD',
                'cluster' => 'council',
                'description' => 'Sectoral Planning Council',
                'is_active' => true,
            ],

            // Research & Development Institutes
            [
                'code' => 'ASTI',
                'name' => 'Advanced Science and Technology Institute',
                'acronym' => 'ASTI',
                'cluster' => 'rdi',
                'description' => 'Research & Development Institute',
                'is_active' => true,
            ],
            [
                'code' => 'FNRI',
                'name' => 'Food and Nutrition Research Institute',
                'acronym' => 'FNRI',
                'cluster' => 'rdi',
                'description' => 'Research & Development Institute',
                'is_active' => true,
            ],
            [
                'code' => 'FPRDI',
                'name' => 'Forest Products Research and Development Institute',
                'acronym' => 'FPRDI',
                'cluster' => 'rdi',
                'description' => 'Research & Development Institute',
                'is_active' => true,
            ],
            [
                'code' => 'ITDI',
                'name' => 'Industrial Technology Development Institute',
                'acronym' => 'ITDI',
                'cluster' => 'rdi',
                'description' => 'Research & Development Institute',
                'is_active' => true,
            ],
            [
                'code' => 'MIRDC',
                'name' => 'Metals Industry Research and Development Center',
                'acronym' => 'MIRDC',
                'cluster' => 'rdi',
                'description' => 'Research & Development Institute',
                'is_active' => true,
            ],
            [
                'code' => 'PNRI',
                'name' => 'Philippine Nuclear Research Institute',
                'acronym' => 'PNRI',
                'cluster' => 'rdi',
                'description' => 'Research & Development Institute',
                'is_active' => true,
            ],
            [
                'code' => 'PTRI',
                'name' => 'Philippine Textile Research Institute',
                'acronym' => 'PTRI',
                'cluster' => 'rdi',
                'description' => 'Research & Development Institute',
                'is_active' => true,
            ],

            // Scientific & Technological Service Institutes
            [
                'code' => 'PAGASA',
                'name' => 'Philippine Atmospheric, Geophysical and Astronomical Services Administration',
                'acronym' => 'PAGASA',
                'cluster' => 'ssi',
                'description' => 'Scientific & Technological Service Institute',
                'is_active' => true,
            ],
            [
                'code' => 'PHIVOLCS',
                'name' => 'Philippine Institute of Volcanology and Seismology',
                'acronym' => 'PHIVOLCS',
                'cluster' => 'ssi',
                'description' => 'Scientific & Technological Service Institute',
                'is_active' => true,
            ],
            [
                'code' => 'PSHS',
                'name' => 'Philippine Science High School System',
                'acronym' => 'PSHS',
                'cluster' => 'ssi',
                'description' => 'Scientific & Technological Service Institute',
                'is_active' => true,
            ],
            [
                'code' => 'SEI',
                'name' => 'Science Education Institute',
                'acronym' => 'SEI',
                'cluster' => 'ssi',
                'description' => 'Scientific & Technological Service Institute',
                'is_active' => true,
            ],
            [
                'code' => 'STII',
                'name' => 'Science and Technology Information Institute',
                'acronym' => 'STII',
                'cluster' => 'ssi',
                'description' => 'Scientific & Technological Service Institute',
                'is_active' => true,
            ],
            [
                'code' => 'TAPI',
                'name' => 'Technology Application and Promotion Institute',
                'acronym' => 'TAPI',
                'cluster' => 'ssi',
                'description' => 'Scientific & Technological Service Institute',
                'is_active' => true,
            ],
            [
                'code' => 'TRC',
                'name' => 'Technology Resource Center',
                'acronym' => 'TRC',
                'cluster' => 'ssi',
                'description' => 'Scientific & Technological Service Institute',
                'is_active' => true,
            ],

            // Collegial / Advisory Bodies
            [
                'code' => 'NAST',
                'name' => 'National Academy of Science and Technology',
                'acronym' => 'NAST',
                'cluster' => 'collegial',
                'description' => 'Collegial / Advisory Body',
                'is_active' => true,
            ],
            [
                'code' => 'NRCP',
                'name' => 'National Research Council of the Philippines',
                'acronym' => 'NRCP',
                'cluster' => 'collegial',
                'description' => 'Collegial / Advisory Body',
                'is_active' => true,
            ],
        ];

        foreach ($agencies as $agency) {
            DOSTAgency::updateOrCreate(
                ['code' => $agency['code']],
                $agency
            );
        }

        $this->command->info('Agencies seeded successfully!');
    }
}
