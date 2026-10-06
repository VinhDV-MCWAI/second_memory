<?php

namespace Database\Seeders;

use App\Models\Management\CategoryMgmt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryMgmtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $categories = [
                [
                    'name' => 'Getting Started',
                    'slug' => 'getting-started',
                    'description' => 'Learn the basics and set up your environment',
                    'rank_order' => 1,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Architecture',
                    'slug' => 'architecture',
                    'description' => 'Understanding the system architecture and design patterns',
                    'rank_order' => 2,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'API Reference',
                    'slug' => 'api-reference',
                    'description' => 'Complete API documentation and endpoints',
                    'rank_order' => 3,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Frontend Development',
                    'slug' => 'frontend',
                    'description' => 'Next.js, React, and UI development guides',
                    'rank_order' => 4,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Backend Development',
                    'slug' => 'backend',
                    'description' => 'Laravel API, database, and server-side logic',
                    'rank_order' => 5,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Database',
                    'slug' => 'database',
                    'description' => 'Database schema, migrations, and best practices',
                    'rank_order' => 6,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Deployment',
                    'slug' => 'deployment',
                    'description' => 'Docker, CI/CD, and production deployment',
                    'rank_order' => 7,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Best Practices',
                    'slug' => 'best-practices',
                    'description' => 'Coding standards and recommended patterns',
                    'rank_order' => 8,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Testing',
                    'slug' => 'testing',
                    'description' => 'Unit tests, integration tests, and testing strategies',
                    'rank_order' => 9,
                    'is_display' => true,
                    'status' => 1,
                ],
                [
                    'name' => 'Security',
                    'slug' => 'security',
                    'description' => 'Authentication, authorization, and security practices',
                    'rank_order' => 10,
                    'is_display' => true,
                    'status' => 1,
                ],
            ];

            foreach ($categories as $category) {
                CategoryMgmt::firstOrCreate(
                    ['slug' => $category['slug']],
                    array_merge($category, [
                        'is_delete' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            $this->command->info('✓ Category Management data seeded successfully');
        });
    }
}
