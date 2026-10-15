<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Models\Master\AdminSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Rows of the sessions screen (AUTH-04); the sessions themselves are in Redis.
 */
final class AdminSessionRepository
{
    /**
     * @return Collection<int, AdminSession>
     */
    public function ofAdmin(int $adminId): Collection
    {
        return AdminSession::query()
            ->where('admin_id', $adminId)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Rows of this admin among the given ids (another admin's ids are left out).
     *
     * @param  list<int>  $ids
     * @return Collection<int, AdminSession>
     */
    public function ofAdminByIds(int $adminId, array $ids): Collection
    {
        return AdminSession::query()->where('admin_id', $adminId)->whereIn('id', $ids)->get();
    }

    public function create(int $adminId, string $sessionId, ?string $ip, ?string $userAgent, Carbon $now): AdminSession
    {
        return AdminSession::query()->updateOrCreate(
            ['session_id' => $sessionId],
            ['admin_id' => $adminId, 'ip_address' => $ip, 'user_agent' => $userAgent, 'last_used_at' => $now],
        );
    }

    public function remember(AdminSession $row, string $tokenHash, Carbon $until): void
    {
        $row->update(['remember_token_hash' => $tokenHash, 'remembered_until' => $until]);
    }

    /**
     * A row whose remember-me has not run out yet.
     */
    public function findRemembered(int $id, Carbon $now): ?AdminSession
    {
        return AdminSession::query()
            ->whereKey($id)
            ->whereNotNull('remember_token_hash')
            ->where('remembered_until', '>', $now)
            ->first();
    }

    /**
     * A remembered device signed in again with a new session: same row, so "signed in" keeps the first login.
     */
    public function attachSession(AdminSession $row, string $sessionId, ?string $ip, Carbon $now): void
    {
        $row->update(['session_id' => $sessionId, 'ip_address' => $ip, 'last_used_at' => $now]);
    }

    /**
     * @return int 0 when the row is gone, i.e. the session was ended from another device
     */
    public function touch(string $sessionId, ?string $ip, Carbon $now): int
    {
        return AdminSession::query()
            ->where('session_id', $sessionId)
            ->update(['ip_address' => $ip, 'last_used_at' => $now]);
    }

    /**
     * @param  list<string>  $sessionIds
     */
    public function deleteBySessionIds(array $sessionIds): int
    {
        return $sessionIds === [] ? 0 : AdminSession::query()->whereIn('session_id', $sessionIds)->delete();
    }

    /**
     * @return list<string>
     */
    public function sessionIdsOfAdmin(int $adminId, ?string $exceptSessionId): array
    {
        return AdminSession::query()
            ->where('admin_id', $adminId)
            ->when($exceptSessionId !== null, fn ($query) => $query->where('session_id', '!=', $exceptSessionId))
            ->pluck('session_id')
            ->all();
    }

    /**
     * Rows unused since $before, unless their remember-me still runs.
     */
    public function deleteUnusedSince(Carbon $before, Carbon $now): int
    {
        return AdminSession::query()
            ->where('last_used_at', '<', $before)
            ->where(fn ($query) => $query->whereNull('remembered_until')->orWhere('remembered_until', '<=', $now))
            ->delete();
    }
}
