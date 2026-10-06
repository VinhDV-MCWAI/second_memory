<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TypeOfMethod;
use App\Models\Master\AdminDepartmentMst;
use App\Models\Master\AdminMst;
use App\Models\Master\AdminRoleMst;
use App\Models\Master\ApiMst;
use App\Models\Master\ApiRoleMst;
use App\Models\Master\DepartmentMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RootAccountSeeder extends Seeder
{
    protected string $prefixApi = 'api/admin/';

    protected string $root = 'root';

    protected string $adminGroup = 'admin';

    /**
     * Run the database seeds.
     * This seeder should be run ONCE during initial project setup.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Step 1: Create root admin account
            $adminAccount = AdminMst::firstOrCreate(
                ['email' => 'root@gmail.com'],
                [
                    'user_name' => $this->root,
                    'password' => bcrypt('12345678'),
                    'first_name' => 'first',
                    'last_name' => 'last',
                    'status' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command->info("✓ Root admin account created/found: {$adminAccount->email}");

            // Step 2: Create root role
            $rootRole = RoleMst::firstOrCreate(
                ['name' => $this->root],
                [
                    'permission' => $this->root,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command->info("✓ Root role created/found: {$rootRole->name}");

            // Step 3: Assign role to admin
            AdminRoleMst::updateOrInsert(
                [
                    'admin_mst_id' => $adminAccount->id,
                    'role_mst_id' => $rootRole->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command->info('✓ Role assigned to admin');

            // Step 4: Create root department
            $department = DepartmentMst::firstOrCreate(
                ['name' => $this->root],
                [
                    'code' => '1',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command->info("✓ Root department created/found: {$department->name}");

            // Step 5: Assign department to admin
            AdminDepartmentMst::updateOrInsert(
                [
                    'admin_mst_id' => $adminAccount->id,
                    'department_mst_id' => $department->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command->info('✓ Department assigned to admin');

            // Step 6: Sync all current routes
            $this->command->info("\n--- Syncing all routes ---");
            $routeCount = 0;

            foreach (Route::getRoutes() as $route) {
                if (Str::startsWith($route->uri(), $this->prefixApi)) {
                    $routeCount++;
                    $this->command->info("Processing: {$route->uri()}");

                    [$controller, $method] = explode('@', class_basename($route->getActionName()));
                    $controllerName = str_replace('Controller', '', $controller);
                    $controllerName = preg_replace('/([a-z])([A-Z])/', '$1 $2', $controllerName);
                    $controllerName = ucwords($controllerName);
                    $method = ucfirst($method);

                    // Create/update feature
                    $feature = FeatureMst::firstOrCreate(
                        ['name' => $controllerName],
                        [
                            'group_name' => $this->adminGroup,
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    // Create/update API
                    $api = ApiMst::firstOrCreate(
                        ['path' => $route->uri()],
                        [
                            'type' => TypeOfMethod::fromName($route->methods()[0]),
                            'name' => $method.' '.$controllerName,
                            'path' => $route->uri(),
                            'is_active' => true,
                            'feature_mst_id' => $feature->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    // Assign API to root role
                    ApiRoleMst::updateOrInsert(
                        [
                            'api_mst_id' => $api->id,
                            'role_mst_id' => $rootRole->id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            $this->command->info("\n✅ Root account seeder completed successfully!");
            $this->command->info("   - Admin: {$adminAccount->email}");
            $this->command->info("   - Role: {$rootRole->name}");
            $this->command->info("   - Department: {$department->name}");
            $this->command->info("   - Routes synced: {$routeCount}");
        });
    }
}
