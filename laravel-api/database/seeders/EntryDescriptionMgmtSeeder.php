<?php

namespace Database\Seeders;

use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Management\EntryMgmt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntryDescriptionMgmtSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $descriptions = [
                // ─── Getting Started ────────────────────────────
                [
                    'entry_slug' => 'installation',
                    'descriptions' => [
                        [
                            'title' => 'Prerequisites',
                            'summary' => 'System requirements and prerequisites for installation',
                            'article' => [
                                'type' => 'doc',
                                'content' => [
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 2],
                                        'content' => [['type' => 'text', 'text' => 'System Requirements']],
                                    ],
                                    [
                                        'type' => 'paragraph',
                                        'content' => [['type' => 'text', 'text' => 'Before installing, ensure your system meets the following requirements:']],
                                    ],
                                    [
                                        'type' => 'bulletList',
                                        'content' => [
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Docker 20.10+']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Docker Compose 2.0+']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '4 GB RAM minimum']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '10 GB free disk space']]]]],
                                        ],
                                    ],
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 3],
                                        'content' => [['type' => 'text', 'text' => 'Required Knowledge']],
                                    ],
                                    [
                                        'type' => 'bulletList',
                                        'content' => [
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Command line interface']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Docker containers']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Environment variables']]]]],
                                        ],
                                    ],
                                ],
                            ],
                            'rank_order' => 1,
                        ],
                        [
                            'title' => 'Clone Repository',
                            'summary' => 'How to clone and set up the project repository',
                            'article' => [
                                'type' => 'doc',
                                'content' => [
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 2],
                                        'content' => [['type' => 'text', 'text' => 'Cloning the Repository']],
                                    ],
                                    [
                                        'type' => 'paragraph',
                                        'content' => [['type' => 'text', 'text' => 'Clone the repository from GitHub:']],
                                    ],
                                    [
                                        'type' => 'codeBlock',
                                        'attrs' => ['language' => 'bash'],
                                        'content' => [['type' => 'text', 'text' => "git clone https://github.com/your-org/second-memory.git\ncd second-memory"]],
                                    ],
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 3],
                                        'content' => [['type' => 'text', 'text' => 'Directory Structure']],
                                    ],
                                    [
                                        'type' => 'bulletList',
                                        'content' => [
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'docker/'], ['type' => 'text', 'text' => ' - Docker configuration files']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'laravel-api/'], ['type' => 'text', 'text' => ' - Backend Laravel application']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'nextjs-docs/'], ['type' => 'text', 'text' => ' - Documentation app']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'nextjs-fe/'], ['type' => 'text', 'text' => ' - Frontend Next.js application']]]]],
                                        ],
                                    ],
                                ],
                            ],
                            'rank_order' => 2,
                        ],
                        [
                            'title' => 'Environment Setup',
                            'summary' => 'Configure environment variables and Docker',
                            'article' => [
                                'type' => 'doc',
                                'content' => [
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 2],
                                        'content' => [['type' => 'text', 'text' => 'Setting Up Environment Variables']],
                                    ],
                                    [
                                        'type' => 'paragraph',
                                        'content' => [['type' => 'text', 'text' => 'Copy the example environment file and configure it:']],
                                    ],
                                    [
                                        'type' => 'codeBlock',
                                        'attrs' => ['language' => 'bash'],
                                        'content' => [['type' => 'text', 'text' => "cd docker\ncp .env.example .env"]],
                                    ],
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 3],
                                        'content' => [['type' => 'text', 'text' => 'Key Variables']],
                                    ],
                                    [
                                        'type' => 'bulletList',
                                        'content' => [
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'POSTGRES_DB'], ['type' => 'text', 'text' => ' - Database name']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'POSTGRES_USER'], ['type' => 'text', 'text' => ' - Database user']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'POSTGRES_PASSWORD'], ['type' => 'text', 'text' => ' - Database password']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'REDIS_PASSWORD'], ['type' => 'text', 'text' => ' - Redis password']]]]],
                                        ],
                                    ],
                                ],
                            ],
                            'rank_order' => 3,
                        ],
                        [
                            'title' => 'Start Services',
                            'summary' => 'Launch Docker containers and verify installation',
                            'article' => [
                                'type' => 'doc',
                                'content' => [
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 2],
                                        'content' => [['type' => 'text', 'text' => 'Starting Docker Services']],
                                    ],
                                    [
                                        'type' => 'paragraph',
                                        'content' => [['type' => 'text', 'text' => 'Use Docker Compose to start all services:']],
                                    ],
                                    [
                                        'type' => 'codeBlock',
                                        'attrs' => ['language' => 'bash'],
                                        'content' => [['type' => 'text', 'text' => 'docker-compose up -d']],
                                    ],
                                    [
                                        'type' => 'heading',
                                        'attrs' => ['level' => 3],
                                        'content' => [['type' => 'text', 'text' => 'Verify Services']],
                                    ],
                                    [
                                        'type' => 'codeBlock',
                                        'attrs' => ['language' => 'bash'],
                                        'content' => [['type' => 'text', 'text' => 'docker-compose ps']],
                                    ],
                                    [
                                        'type' => 'paragraph',
                                        'content' => [['type' => 'text', 'text' => 'You should see:']],
                                    ],
                                    [
                                        'type' => 'bulletList',
                                        'content' => [
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ml-postgres (PostgreSQL database)']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ml-redis (Redis cache)']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ml-php (Laravel PHP-FPM)']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ml-nextjs (Next.js frontend)']]]]],
                                            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ml-nginx (Nginx web server)']]]]],
                                        ],
                                    ],
                                ],
                            ],
                            'rank_order' => 4,
                        ],
                    ],
                ],
            ];

            foreach ($descriptions as $entryGroup) {
                $entry = EntryMgmt::where('slug', $entryGroup['entry_slug'])->first();

                if (! $entry) {
                    continue;
                }

                foreach ($entryGroup['descriptions'] as $descData) {
                    EntryDescriptionMgmt::create([
                        'title' => $descData['title'],
                        'summary' => $descData['summary'],
                        'article' => json_encode($descData['article']),
                        'status' => 1,
                        'is_display' => true,
                        'rank_order' => $descData['rank_order'],
                        'is_delete' => false,
                    ]);
                }
            }
        });
    }
}
