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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
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
 *
 * "Remember me" (AUTH-05) is per device: the device's cookie carries a token whose hash is on its row.
 * When the session has expired, RestoreRememberedSession signs the device in again on the same row;
 * ending the session (deleting the row) therefore also forgets the device, and no other device.
 */
final class AdminSessionService
{
    /** audit_log.auditable_type of admins (AdminMstService::$auditableType). */
    private const AUDITABLE_TYPE = 'admin';

    private const REMEMBER_TOKEN_LENGTH = 60;

    private const REMEMBER_SEPARATOR = '|';

    public function __construct(
        private readonly AdminSessionRepository $sessions,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Called right after a login: list this session, and remember the device when asked.
     */
    public function start(int $adminId, Request $request, bool $remember): void
    {
        $session = $request->session();
        $now = Carbon::now();
        $row = $this->sessions->create($adminId, $session->getId(), $request->ip(), $this->userAgent($request), $now);
        $this->mark($session, $now);

        if ($remember) {
            $token = Str::random(self::REMEMBER_TOKEN_LENGTH);
            $this->sessions->remember($row, hash('sha256', $token), $now->copy()->addDays(CommonVal::REMEMBER_DAYS));
            Cookie::queue(Cookie::make(
                CommonVal::REMEMBER_COOKIE,
                $row->id.self::REMEMBER_SEPARATOR.$token,
                CommonVal::REMEMBER_DAYS * 24 * 60,
                secure: config('session.secure'),
                sameSite: 'lax',
            ));
        }
    }

    /**
     * A request without a signed-in admin that carries a remember-me cookie: sign that device in again.
     *
     * @return bool false when the cookie no longer counts (signed out, expired, admin disabled); it is dropped
     */
    public function restore(Request $request): bool
    {
        [$id, $token] = array_pad(explode(self::REMEMBER_SEPARATOR, (string) $request->cookie(CommonVal::REMEMBER_COOKIE), 2), 2, '');
        $now = Carbon::now();
        $row = ctype_digit($id) ? $this->sessions->findRemembered((int) $id, $now) : null;
        $guard = Auth::guard(CommonVal::ADMIN_GUARD);
        // The provider only returns active, not deleted admins
        $admin = $row !== null && hash_equals((string) $row->remember_token_hash, hash('sha256', $token))
            ? $guard->getProvider()->retrieveById($row->admin_id)
            : null;
        if ($row === null || $admin === null) {
            $this->forgetDevice();

            return false;
        }

        $guard->login($admin);
        $session = $request->session();
        $session->regenerate();
        SessionStore::getHandler()->destroy($row->session_id);
        $this->sessions->attachSession($row, $session->getId(), $request->ip(), $now);
        $this->mark($session, $now);
        $this->auditLogger->record(self::AUDITABLE_TYPE, $row->admin_id, AuditEvent::LOGGED_IN, new: ['via' => 'remembered_device'], actorId: $row->admin_id);

        return true;
    }

    /**
     * Log out: the device is no longer remembered.
     */
    public function forgetDevice(): void
    {
        Cookie::queue(Cookie::forget(CommonVal::REMEMBER_COOKIE));
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
     * This admin's sessions, newest first. Rows whose Redis session expired are removed on the way,
     * unless the device is remembered (it signs in again on its next request).
     *
     * @return Collection<int, AdminSession>
     */
    public function list(int $adminId): Collection
    {
        $rows = $this->sessions->ofAdmin($adminId);
        $now = Carbon::now();
        $expired = $rows->filter(fn (AdminSession $row): bool => ! $row->remembered_until?->isAfter($now)
            && SessionStore::getHandler()->read($row->session_id) === '');
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
     * Rows of sessions unused for longer than a session lives (Redis has dropped them) and not remembered.
     */
    public function pruneExpired(): int
    {
        $lifetimeSeconds = (int) config('session.lifetime') * 60 + CommonVal::SESSION_TOUCH_SECONDS;
        $now = Carbon::now();

        return $this->sessions->deleteUnusedSince($now->copy()->subSeconds($lifetimeSeconds), $now);
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
