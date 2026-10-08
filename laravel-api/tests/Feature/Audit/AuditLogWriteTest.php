<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Enums\AuditEvent;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * ADR-0006: admin changes are written to audit_log.
 */
final class AuditLogWriteTest extends TestCase
{
    use AuthenticatesAdmins;

    private AdminMst $owner;

    /** @var array<string, string> */
    private array $cookies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = AdminMst::factory()->create();
        $this->cookies = $this->loginAsOwner($this->owner);
    }

    public function test_update_logs_only_the_changed_fields_with_the_actor(): void
    {
        $target = AdminMst::factory()->create(['first_name' => 'Before']);

        $this->call('PUT', "api/admin/admin-mst/update/{$target->id}", [
            'email' => $target->email,
            'user_name' => $target->user_name,
            'first_name' => 'After',
            'last_name' => $target->last_name,
            'gender' => $target->gender,
            'status' => $target->status,
            'is_active' => 1,
            'password' => 'new-secret-1',
        ], $this->cookies)->assertOk();

        $log = AuditLog::where('auditable_type', 'admin')->where('auditable_id', $target->id)->sole();
        $this->assertSame(AuditEvent::UPDATED, $log->event);
        $this->assertSame(['first_name' => 'Before'], $log->old_values);
        $this->assertSame(['first_name' => 'After'], $log->new_values);
        $this->assertSame($this->owner->id, $log->admin_mst_id);
        $this->assertNotNull($log->ip_address);
    }

    public function test_create_and_delete_log_the_full_row_without_secrets(): void
    {
        $this->call('POST', 'api/admin/admin-mst/store', [
            'email' => 'audit.me@gmail.com',
            'user_name' => 'audit_me',
            'password' => 'password',
            'first_name' => 'Audit',
            'last_name' => 'Me',
            'gender' => 1,
            'status' => 1,
            'is_active' => 1,
            'is_delete' => 0,
        ], $this->cookies)->assertOk();
        $id = (int) AdminMst::where('user_name', 'audit_me')->value('id');

        $this->call('POST', 'api/admin/admin-mst/delete', ['ids' => [$id]], $this->cookies)->assertOk();

        $logs = AuditLog::where('auditable_id', $id)->orderBy('id')->get();
        $this->assertSame([AuditEvent::CREATED, AuditEvent::DELETED], $logs->pluck('event')->all());
        $this->assertNull($logs[0]->old_values);
        $this->assertSame('audit_me', $logs[0]->new_values['user_name']);
        $this->assertSame('audit_me', $logs[1]->old_values['user_name']);
        $this->assertNull($logs[1]->new_values);

        $raw = (string) DB::table('audit_log')->where('auditable_id', $id)->pluck('new_values')->implode(' ');
        $this->assertStringNotContainsString('password', $raw);
    }
}
