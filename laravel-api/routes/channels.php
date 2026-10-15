<?php

declare(strict_types=1);

use App\Constants\BackgroundTaskConst;
use App\Models\Master\AdminMst;
use Illuminate\Support\Facades\Broadcast;

// Live notifications (ADR-0014): each admin listens only to their own channel. Authorized by
// POST /api/broadcasting/auth with the Sanctum session cookie (bootstrap/app.php).
Broadcast::channel(BackgroundTaskConst::CHANNEL_PREFIX.'{id}', fn (AdminMst $admin, string $id): bool => $admin->id === (int) $id);
