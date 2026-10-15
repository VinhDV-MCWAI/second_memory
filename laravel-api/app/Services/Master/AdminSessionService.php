<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Enums\AuditEvent;
use App\Models\Master\AdminSession;
use App\Repositories\Master\AdminSessionRepository;
use App\Services\AuditLogger;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session as SessionStore;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sessions screen (AUTH-04): the devices an admin is signed in on, and ending them from another device.
 *
 * Sessions stay in Redis (fast with many admins). Each one has an `admin_session` row, written at login
 * and refreshed by TrackAdminSession at most every SESSION_TOUCH_SECONDS. Ending a session deletes its
 * Redis key (the next request is a guest, 401) and its row; a request already running on that device
 * could write the session back, so a session whose row is gone is also logged out at its next refresh.
 */
final class AdminSessionService
{
    /** audit_log.auditable_type of admins (AdminMstService::$auditableType). */
    private const AUDITABLE_TYPE = 'admin';

    public function __construct(
        private readonly AdminSessionRepository $sessions,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Called right after a login: list this session.
     */
    public function start(int $adminId, Request $request): void
    {
        $session = $request->session();
        $now = Carbon::now();
        $this->sessions->create($adminId, $session->getId(), $request->ip(), $this->userAgent($request), $now);
        $this->mark($session, $now);
    }

    /**
     * Called on every admin request of a session login.
     *
     * @return bool false when this session was ended from another device
     */
    public function track(int $adminId, Request $request): bool
    {
        $session = $request->session();
        /** @var array{session_id?: string, at?: int}|null $mark */
        $mark = $session->get(CommonVal::SESSION_TRACKING_KEY);
        $now = Carbon::now();

        // Signed in before this feature, or the session id changed: (re)list it
        if (($mark['session_id'] ?? null) !== $session->getId()) {
            if (isset($mark['session_id'])) {
                $this->sessions->deleteBySessionIds([$mark['session_id']]);
            }
            $this->sessions->create($adminId, $session->getId(), $request->ip(), $this->userAgent($request), $now);
            $this->mark($session, $now);

            return true;
        }

        if ($now->getTimestamp() - ($mark['at'] ?? 0) < CommonVal::SESSION_TOUCH_SECONDS) {
            return true;
        }
        if ($this->sessions->touch($session->getId(), $request->ip(), $now) === 0) {
            return false;
        }
        $this->mark($session, $now);

        return true;
    }

    /**
     * This admin's sessions, newest first. Rows whose Redis session expired are removed on the way.
     *
     * @return Collection<int, AdminSession>
     */
    public function list(int $adminId): Collection
    {
        $rows = $this->sessions->ofAdmin($adminId);
        $expired = $rows->filter(fn (AdminSession $row): bool => SessionStore::getHandler()->read($row->session_id) === '');
        $this->sessions->deleteBySessionIds($expired->pluck('session_id')->all());

        return $rows->diff($expired)->values();
    }

    /**
     * End some of this admin's other sessions (ids of another admin are ignored).
     *
     * @param  list<int>  $ids
     * @return int how many were ended
     *
     * @throws ValidationException when the list holds the session in use (that one ends with log out)
     */
    public function end(int $adminId, array $ids, string $currentSessionId): int
    {
        $sessionIds = $this->sessions->ofAdminByIds($adminId, $ids)->pluck('session_id')->all();
        if (in_array($currentSessionId, $sessionIds, true)) {
            throw ValidationException::withMessages(['ids' => Messages::E0043]);
        }

        return $this->endSessions($adminId, $sessionIds, $adminId);
    }

    /**
     * End every session of the admin, except one (the session in use when the admin acts on themselves).
     *
     * @return int how many were ended
     */
    public function endAll(int $adminId, ?string $exceptSessionId = null, ?int $actorId = null): int
    {
        return $this->endSessions($adminId, $this->sessions->sessionIdsOfAdmin($adminId, $exceptSessionId), $actorId);
    }

    /**
     * Log out: the row of the session goes with it.
     */
    public function forget(string $sessionId): void
    {
        $this->sessions->deleteBySessionIds([$sessionId]);
    }

    /**
     * Rows of sessions unused for longer than a session lives (Redis has dropped them).
     */
    public function pruneExpired(): int
    {
        $lifetimeSeconds = (int) config('session.lifetime') * 60 + CommonVal::SESSION_TOUCH_SECONDS;

        return $this->sessions->deleteUnusedSince(Carbon::now()->subSeconds($lifetimeSeconds));
    }

    /**
     * @param  list<string>  $sessionIds
     */
    private function endSessions(int $adminId, array $sessionIds, ?int $actorId): int
    {
        if ($sessionIds === []) {
            return 0;
        }
        // Only once the rows are gone for good: a rolled back request must not have logged anyone out
        DB::afterCommit(function () use ($sessionIds): void {
            foreach ($sessionIds as $sessionId) {
                SessionStore::getHandler()->destroy($sessionId);
            }
        });
        $ended = $this->sessions->deleteBySessionIds($sessionIds);
        $this->auditLogger->record(self::AUDITABLE_TYPE, $adminId, AuditEvent::LOGGED_OUT, new: ['ended_sessions' => $ended], actorId: $actorId);

        return $ended;
    }

    private function mark(Session $session, Carbon $now): void
    {
        $session->put(CommonVal::SESSION_TRACKING_KEY, ['session_id' => $session->getId(), 'at' => $now->getTimestamp()]);
    }

    private function userAgent(Request $request): ?string
    {
        $userAgent = $request->userAgent();

        return $userAgent === null ? null : Str::limit($userAgent, CommonVal::USER_AGENT_MAX, '');
    }
}
