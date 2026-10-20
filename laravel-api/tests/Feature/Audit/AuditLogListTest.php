<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Constants\CommonVal;
use App\Enums\AuditEvent;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * ADR-0006 read side: GET /api/admin/audit-log/list.
 */
final class AuditLogListTest extends TestCase
{
    use AuthenticatesAdmins;

    private const string URL = 'api/admin/audit-log/list';

    public function test_requires_a_session(): void
    {
        $this->getJson(self::URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_lists_one_records_trail_newest_first_for_any_admin(): void
    {
        $owner = AdminMst::factory()->create();
        $ownerCookies = $this->loginAsOwner($owner);
        $target = AdminMst::factory()->create();
        $other = AdminMst::factory()->create();

        foreach (['Second', 'Third'] as $name) {
            $this->call('PUT', "api/admin/admin-mst/update/{$target->id}", [
                'email' => $target->email, 'user_name' => $target->user_name, 'first_name' => $name,
                'last_name' => $target->last_name, 'gender' => $target->gender, 'status' => $target->status, 'is_active' => 1,
            ], $ownerCookies)->assertOk();
        }
        $this->call('POST', 'api/admin/admin-mst/delete', ['ids' => [$other->id]], $ownerCookies)->assertOk();

        // A viewer may read the audit log like any other admin data
        $viewerCookies = $this->loginAs(AdminMst::factory()->create());
        $response = $this->call('GET', self::URL, ['auditable_type' => 'admin', 'auditable_id' => $target->id], $viewerCookies);

        $response->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.event', 'updated')
            ->assertJsonPath('data.data.0.new_values.first_name', 'Third')
            ->assertJsonPath('data.data.1.new_values.first_name', 'Second')
            ->assertJsonPath('data.data.0.actor_user_name', $owner->user_name);
    }

    public function test_rejects_an_unknown_event_filter(): void
    {
        $cookies = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::URL, ['event' => 'hacked'], $cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_filters_by_day_month_year_dates(): void
    {
        $cookies = $this->loginAs(AdminMst::factory()->create());
        foreach (['2026-01-02 09:00:00', '2026-02-01 09:00:00', '2026-10-14 23:30:00'] as $at) {
            AuditLog::query()->create(['auditable_type' => 'article', 'event' => AuditEvent::CREATED, 'created_at' => $at]);
        }

        // 02/01/2026 is 2 January, not 1 February
        $this->call('GET', self::URL, ['auditable_type' => 'article', 'from_date' => '02/01/2026', 'to_date' => '02/01/2026'], $cookies)
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.created_at', '2026-01-02T09:00:00+00:00');

        // A day above 12 used to fail with 500; the whole last day is included
        $this->call('GET', self::URL, ['auditable_type' => 'article', 'from_date' => '14/10/2026', 'to_date' => '14/10/2026'], $cookies)
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }
}
