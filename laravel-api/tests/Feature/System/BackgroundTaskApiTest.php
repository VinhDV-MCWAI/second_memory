<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Constants\BackgroundTaskConst;
use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Enums\BackgroundTaskStatus;
use App\Events\BackgroundTaskFinished;
use App\Jobs\System\TestBackgroundJob;
use App\Models\Master\AdminMst;
use App\Services\BackgroundTasks\BackgroundTaskTracker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Background jobs and live notifications (ADR-0014).
 */
final class BackgroundTaskApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/background-task';

    private AdminMst $admin;

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminMst::factory()->create();
        $this->owner = $this->loginAsOwner($this->admin);
    }

    public function test_the_test_job_is_queued_and_its_status_readable_by_its_owner_only(): void
    {
        Queue::fake();

        $task = $this->call('POST', self::BASE.'/test', ['seconds' => 0], $this->owner)
            ->assertOk()
            ->assertJsonPath('data.data.type', BackgroundTaskConst::TYPE_TEST)
            ->assertJsonPath('data.data.status', BackgroundTaskStatus::QUEUED->value)
            ->json('data.data');
        Queue::assertPushed(TestBackgroundJob::class, fn (TestBackgroundJob $job): bool => $job->taskId === $task['id']);

        $this->call('GET', self::BASE."/status/{$task['id']}", [], $this->owner)
            ->assertOk()->assertJsonPath('data.data.status', BackgroundTaskStatus::QUEUED->value);

        $other = $this->loginAsOwner(AdminMst::factory()->create());
        $this->call('GET', self::BASE."/status/{$task['id']}", [], $other)->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_a_finished_job_is_broadcast_to_its_admin(): void
    {
        Event::fake([BackgroundTaskFinished::class]);
        $tracker = app(BackgroundTaskTracker::class);
        $job = new TestBackgroundJob($this->admin->id, 0, false);
        Queue::fake();
        $tracker->dispatch($job);

        app()->call([$job, 'handle']);

        Event::assertDispatched(BackgroundTaskFinished::class, function (BackgroundTaskFinished $event): bool {
            return $event->broadcastOn()->name === 'private-'.BackgroundTaskConst::CHANNEL_PREFIX.$this->admin->id
                && $event->broadcastAs() === BackgroundTaskConst::FINISHED_EVENT
                && $event->broadcastWith()['status'] === BackgroundTaskStatus::COMPLETED->value
                && ! array_key_exists('admin_id', $event->broadcastWith());
        });
        $this->assertSame(BackgroundTaskStatus::COMPLETED->value, $tracker->find($job->taskId, $this->admin->id)['status'] ?? null);
    }

    public function test_a_failed_job_reports_a_generic_message(): void
    {
        Event::fake([BackgroundTaskFinished::class]);
        Queue::fake();
        $job = new TestBackgroundJob($this->admin->id, 0, true);
        app(BackgroundTaskTracker::class)->dispatch($job);

        try {
            app()->call([$job, 'handle']);
            $this->fail('The test job should throw');
        } catch (RuntimeException) {
            $job->failed(null);
        }

        Event::assertDispatched(BackgroundTaskFinished::class, fn (BackgroundTaskFinished $event): bool => $event->task['status'] === BackgroundTaskStatus::FAILED->value
            && $event->task['message'] === Messages::E0036);
    }

    public function test_validation_and_viewer_access(): void
    {
        $this->call('POST', self::BASE.'/test', ['seconds' => BackgroundTaskConst::TEST_MAX_SECONDS + 1], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $viewer = $this->loginAs(AdminMst::factory()->create());
        $this->call('POST', self::BASE.'/test', [], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('GET', self::BASE.'/status/unknown', [], $viewer)->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_an_admin_may_subscribe_only_to_their_own_channel(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        // Channels were registered on the null broadcaster at boot; register them on the real one
        require base_path('routes/channels.php');
        $channel = fn (int $id): array => ['socket_id' => '1234.5678', 'channel_name' => 'private-'.BackgroundTaskConst::CHANNEL_PREFIX.$id];

        $this->call('POST', '/api/broadcasting/auth', $channel($this->admin->id), $this->owner)
            ->assertOk()->assertJsonStructure(['auth']);
        $this->call('POST', '/api/broadcasting/auth', $channel($this->admin->id + 1), $this->owner)
            ->assertStatus(CommonVal::HTTP_FORBIDDEN);

        $this->flushSession();
        $this->call('POST', '/api/broadcasting/auth', $channel($this->admin->id))->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }
}
