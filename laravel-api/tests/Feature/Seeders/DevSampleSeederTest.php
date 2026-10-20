<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use App\Models\User\EndUser;
use Database\Seeders\DevSampleSeeder;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

final class DevSampleSeederTest extends TestCase
{
    use AuthenticatesAdmins;

    public function test_running_twice_creates_the_sample_accounts_once(): void
    {
        $this->seed(DevSampleSeeder::class);
        $this->seed(DevSampleSeeder::class);

        $this->assertSame(1, AdminMst::query()->where('user_name', DevSampleSeeder::VIEWER_USER_NAME)->count());
        $this->assertSame(1, EndUser::query()->where('username', DevSampleSeeder::END_USER_NAME)->count());
    }

    public function test_viewer_account_can_read_but_not_write(): void
    {
        $this->seed(DevSampleSeeder::class);
        $viewer = AdminMst::query()->where('user_name', DevSampleSeeder::VIEWER_USER_NAME)->firstOrFail();
        $this->assertSame([AdminRole::VIEWER->value], $viewer->getRoleNames()->all());

        $cookies = $this->loginAs($viewer, DevSampleSeeder::VIEWER_PASSWORD);

        $this->call('GET', '/api/admin/article/list', [], $cookies)->assertOk();
        $this->call('POST', '/api/admin/content-category/store', ['name' => 'Go'], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }
}
