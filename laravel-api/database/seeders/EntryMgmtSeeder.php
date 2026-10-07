<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Management\CategoryMgmt;
use App\Models\Management\EntryMgmt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntryMgmtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Define entries with their categories
            $entries = [
                // Getting Started entries
                [
                    'category_slug' => 'getting-started',
                    'entries' => [
                        [
                            'name' => 'Installation',
                            'slug' => 'installation',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Project Structure',
                            'slug' => 'project-structure',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Configuration',
                            'slug' => 'configuration',
                            'rank_order' => 3,
                        ],
                    ],
                ],
                // Architecture entries
                [
                    'category_slug' => 'architecture',
                    'entries' => [
                        [
                            'name' => 'Overview',
                            'slug' => 'overview',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Layered Architecture',
                            'slug' => 'layered-architecture',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Design Patterns',
                            'slug' => 'design-patterns',
                            'rank_order' => 3,
                        ],
                    ],
                ],
                // API Reference entries
                [
                    'category_slug' => 'api-reference',
                    'entries' => [
                        [
                            'name' => 'Authentication',
                            'slug' => 'authentication',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Categories API',
                            'slug' => 'categories-api',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Entries API',
                            'slug' => 'entries-api',
                            'rank_order' => 3,
                        ],
                        [
                            'name' => 'Search API',
                            'slug' => 'search-api',
                            'rank_order' => 4,
                        ],
                    ],
                ],
                // Frontend entries
                [
                    'category_slug' => 'frontend',
                    'entries' => [
                        [
                            'name' => 'Next.js Setup',
                            'slug' => 'nextjs-setup',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Routing',
                            'slug' => 'routing',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'State Management',
                            'slug' => 'state-management',
                            'rank_order' => 3,
                        ],
                        [
                            'name' => 'Styling with Tailwind',
                            'slug' => 'styling-tailwind',
                            'rank_order' => 4,
                        ],
                    ],
                ],
                // Backend entries
                [
                    'category_slug' => 'backend',
                    'entries' => [
                        [
                            'name' => 'Laravel Setup',
                            'slug' => 'laravel-setup',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Controllers & Services',
                            'slug' => 'controllers-services',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Repository Pattern',
                            'slug' => 'repository-pattern',
                            'rank_order' => 3,
                        ],
                        [
                            'name' => 'API Resources',
                            'slug' => 'api-resources',
                            'rank_order' => 4,
                        ],
                    ],
                ],
                // Database entries
                [
                    'category_slug' => 'database',
                    'entries' => [
                        [
                            'name' => 'Migrations',
                            'slug' => 'migrations',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Models & Relations',
                            'slug' => 'models-relations',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Seeders',
                            'slug' => 'seeders',
                            'rank_order' => 3,
                        ],
                    ],
                ],
                // Deployment entries
                [
                    'category_slug' => 'deployment',
                    'entries' => [
                        [
                            'name' => 'Docker Configuration',
                            'slug' => 'docker-configuration',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Environment Variables',
                            'slug' => 'environment-variables',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Production Setup',
                            'slug' => 'production-setup',
                            'rank_order' => 3,
                        ],
                    ],
                ],
                // Best Practices entries
                [
                    'category_slug' => 'best-practices',
                    'entries' => [
                        [
                            'name' => 'Coding Conventions',
                            'slug' => 'coding-conventions',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Error Handling',
                            'slug' => 'error-handling',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Performance Optimization',
                            'slug' => 'performance-optimization',
                            'rank_order' => 3,
                        ],
                    ],
                ],
                // Testing entries
                [
                    'category_slug' => 'testing',
                    'entries' => [
                        [
                            'name' => 'Unit Testing',
                            'slug' => 'unit-testing',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Integration Testing',
                            'slug' => 'integration-testing',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'E2E Testing',
                            'slug' => 'e2e-testing',
                            'rank_order' => 3,
                        ],
                    ],
                ],
                // Security entries
                [
                    'category_slug' => 'security',
                    'entries' => [
                        [
                            'name' => 'JWT Authentication',
                            'slug' => 'jwt-authentication',
                            'rank_order' => 1,
                        ],
                        [
                            'name' => 'Authorization',
                            'slug' => 'authorization',
                            'rank_order' => 2,
                        ],
                        [
                            'name' => 'Security Best Practices',
                            'slug' => 'security-best-practices',
                            'rank_order' => 3,
                        ],
                    ],
                ],
            ];

            foreach ($entries as $categoryData) {
                $category = CategoryMgmt::where('slug', $categoryData['category_slug'])->first();

                if (! $category) {
                    $this->command->warn("Category {$categoryData['category_slug']} not found");

                    continue;
                }

                foreach ($categoryData['entries'] as $entryData) {
                    $entry = EntryMgmt::firstOrCreate(
                        ['slug' => $entryData['slug']],
                        array_merge($entryData, [
                            'status' => 1,
                            'is_display' => true,
                            'is_delete' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );

                    // Store the relationship in category's layout_structure
                    $layoutStructure = $category->layout_structure ?? [];

                    // Check if entry is not already in layout structure
                    $entryExists = false;
                    foreach ($layoutStructure as $item) {
                        if (isset($item['entry_id']) && $item['entry_id'] === $entry->id) {
                            $entryExists = true;
                            break;
                        }
                    }

                    if (! $entryExists) {
                        $layoutStructure[] = [
                            'entry_id' => $entry->id,
                            'rank_order' => $entryData['rank_order'] ?? 0,
                        ];

                        $category->layout_structure = $layoutStructure;
                        $category->save();
                    }
                }
            }

            $this->command->info('✓ Entry Management data seeded successfully');
        });
    }
}
