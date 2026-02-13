<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Chapter;

/**
 * PDP (Philippine Development Plan) Chapters Seeder
 *
 * This seeder creates the standard PDP chapters based on the
 * Philippine Development Plan 2023-2028 framework.
 *
 * PDP Structure:
 * - Chapters are numbered 1-10
 * - Each chapter has a code (PDP01, PDP02, etc.)
 * - Each chapter has a title in Filipino/English
 * - Each chapter has an outcome description
 */
class PDPChapterSeeder extends Seeder
{
    /**
     * Run the database seeds for PDP chapters.
     *
     * @return void
     */
    public function run(): void
    {
        $now = now();

        // PDP Chapters based on Philippine Development Plan 2023-2028
        $chapters = [
            [
                'category'    => 'pdp',
                'code'        => 'PDP01',
                'title'       => 'Chapter 1: Matatag, Agham, at Teknolohiya',
                'outcome'     => 'Strengthening Science, Technology and Innovation',
                'description' => 'Focus on advancing research and development, enhancing innovation capabilities, and promoting science education.',
                'sort_order'  => 1,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP02',
                'title'       => 'Chapter 2: Mahusay na Pagpapatupad at Paghahatid',
                'outcome'     => 'Improving Implementation and Service Delivery',
                'description' => 'Enhance the efficiency and effectiveness of government programs and services.',
                'sort_order'  => 2,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP03',
                'title'       => 'Chapter 3: Malusog at Magalang na Populasyon',
                'outcome'     => 'Promoting Health and Well-being',
                'description' => 'Improve health outcomes, strengthen healthcare systems, and promote healthy lifestyles.',
                'sort_order'  => 3,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP04',
                'title'       => 'Chapter 4: Maunlad na Ekonomiya',
                'outcome'     => 'Fostering Economic Prosperity',
                'description' => 'Drive inclusive economic growth, create quality jobs, and support entrepreneurship.',
                'sort_order'  => 4,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP05',
                'title'       => 'Chapter 5: Pantay at Makabuluhang Pagkakakaloob',
                'outcome'     => 'Ensuring Equal and Meaningful Access',
                'description' => 'Provide equitable access to quality education, healthcare, and social services.',
                'sort_order'  => 5,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP06',
                'title'       => 'Chapter 6: Maaliwalas na Kapaligiran',
                'outcome'     => 'Protecting the Environment',
                'description' => 'Promote sustainable development, climate resilience, and environmental conservation.',
                'sort_order'  => 6,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP07',
                'title'       => 'Chapter 7: Mapayapang Magulang at Matatag na Pamilya',
                'outcome'     => 'Strengthening Families and Communities',
                'description' => 'Support family welfare, protect vulnerable groups, and build resilient communities.',
                'sort_order'  => 7,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP08',
                'title'       => 'Chapter 8: Mapagkalinga at Ligtas na Lipunan',
                'outcome'     => 'Building a Caring and Safe Society',
                'description' => 'Ensure peace and order, protect human rights, and promote social justice.',
                'sort_order'  => 8,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP09',
                'title'       => 'Chapter 9: Pagkilos at Pakikilahok',
                'outcome'     => 'Promoting Action and Participation',
                'description' => 'Encourage civic engagement, volunteerism, and people participation in governance.',
                'sort_order'  => 9,
                'is_active'   => true,
            ],
            [
                'category'    => 'pdp',
                'code'        => 'PDP10',
                'title'       => 'Chapter 10: Mabuting Paggobyerno',
                'outcome'     => 'Ensuring Good Governance',
                'description' => 'Strengthen institutions, fight corruption, and promote transparency and accountability.',
                'sort_order'  => 10,
                'is_active'   => true,
            ],
        ];

        // Insert or update each chapter
        foreach ($chapters as $chapterData) {
            Chapter::updateOrCreate(
                [
                    'category' => $chapterData['category'],
                    'code'     => $chapterData['code'],
                ],
                array_merge($chapterData, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('PDP Chapters seeded successfully.');
    }
}
