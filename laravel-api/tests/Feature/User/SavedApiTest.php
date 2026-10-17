<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Constants\CommonVal;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\User\EndUser;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\AuthenticatesEndUsers;
use Tests\TestCase;

/**
 * Favourites and read later (ADR-0020): only what the public site shows.
 */
final class SavedApiTest extends TestCase
{
    use AuthenticatesAdmins, AuthenticatesEndUsers;

    private const BASE = '/api/user/saved';

    private Skill $skill;

    private Evidence $evidence;

    /** @var array<string, string> */
    private array $cookies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = Skill::factory()->create(['is_public' => true, 'name' => 'PostgreSQL']);
        $this->evidence = Evidence::factory()->create(['is_public' => true, 'title' => 'EXPLAIN notes']);
        $this->evidence->skills()->attach($this->skill->id);
        $this->cookies = $this->loginAsEndUser(EndUser::factory()->create());
    }

    public function test_save_list_and_remove_skills_and_evidence(): void
    {
        $key = $this->call('GET', '/api/public/skills/'.$this->skill->slug)->assertOk()->json('data.data.evidence.0.key');
        $this->assertIsString($key);

        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'skill' => $this->skill->slug], $this->cookies)->assertOk();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'skill' => $this->skill->slug], $this->cookies)->assertOk();
        $this->call('POST', self::BASE.'/store', ['kind' => 'read_later', 'evidence' => $key], $this->cookies)->assertOk();

        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.target', 'evidence')
            ->assertJsonPath('data.data.0.key', $key)
            ->assertJsonPath('data.data.0.title', 'EXPLAIN notes')
            ->assertJsonPath('data.data.0.skill_slug', $this->skill->slug)
            ->assertJsonPath('data.data.1.kind', 'favorite')
            ->assertJsonPath('data.data.1.target', 'skill')
            ->assertJsonPath('data.data.1.title', 'PostgreSQL');
        $this->call('GET', self::BASE.'/list', ['kind' => 'favorite'], $this->cookies)->assertJsonCount(1, 'data.data');

        $this->call('POST', self::BASE.'/delete', ['kind' => 'favorite', 'skill' => $this->skill->slug], $this->cookies)->assertOk();
        $this->call('GET', self::BASE.'/list', ['kind' => 'favorite'], $this->cookies)->assertJsonCount(0, 'data.data');
    }

    public function test_private_or_hidden_things_cannot_be_saved_and_drop_out_of_the_list(): void
    {
        $private = Skill::factory()->create(['is_public' => false]);
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'skill' => $private->slug], $this->cookies)->assertNotFound();

        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'skill' => $this->skill->slug], $this->cookies)->assertOk();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'evidence' => $this->evidence->refresh()->public_key], $this->cookies)->assertOk();

        $this->evidence->forceFill(['is_public' => false])->save();
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.target', 'skill');

        $this->skill->forceFill(['is_public' => false])->save();
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertJsonCount(0, 'data.data');
    }

    public function test_one_target_and_a_known_list(): void
    {
        $this->call('POST', self::BASE.'/store', ['kind' => 'wish', 'skill' => $this->skill->slug, 'evidence' => (string) $this->evidence->refresh()->public_key], $this->cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['kind', 'skill']]]);
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite'], $this->cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_guests_get_401(): void
    {
        $this->call('GET', self::BASE.'/list')->assertUnauthorized();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'skill' => $this->skill->slug])->assertUnauthorized();
    }

    public function test_deleting_the_skill_removes_what_pointed_at_it(): void
    {
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'skill' => $this->skill->slug], $this->cookies)->assertOk();
        $this->skill->delete();

        $this->assertDatabaseCount('end_user_saved', 0);
    }
}
