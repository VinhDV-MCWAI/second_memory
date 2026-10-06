<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('upload.status.{roomId}', function ($user, $roomId) {
    // WebSocket Channel Authorization
    // Room Format: "{uuid}_{adminId}_upload_file"
    // Example: "550e8400-e29b-41d4-a716-446655440000_123_upload_file"

    \Illuminate\Support\Facades\Log::info('[Channel Authorization] upload.status', [
        'roomId' => $roomId,
        'user' => $user,
        'user_type' => gettype($user),
        'user_id' => $user ? $user->id ?? 'no_id' : 'null_user',
    ]);

    $parts = explode('_', $roomId);
    // Expected parts: [uuid, adminId, 'upload', 'file']
    if (count($parts) >= 4) {
        $adminId = $parts[1];

        \Illuminate\Support\Facades\Log::info('[Channel Authorization] Comparing IDs', [
            'adminId_from_room' => $adminId,
            'user_id' => $user->id,
            'match' => (int) $adminId === (int) $user->id,
        ]);

        // Verify: adminId from roomId matches authenticated user's id
        // $user is set by AdminMiddleware->setUserResolver()
        // which contains the decoded JWT payload with user id
        return (int) $adminId === (int) $user->id;
    }

    \Illuminate\Support\Facades\Log::warning('[Channel Authorization] Invalid room format', [
        'roomId' => $roomId,
        'parts_count' => count($parts),
    ]);

    return false;
});
