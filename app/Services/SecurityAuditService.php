<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityAuditService
{
    public function log(
        string $eventType,
        ?int $actorUserId = null,
        ?int $targetUserId = null,
        ?string $reason = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): SecurityAuditLog {
        $entry = SecurityAuditLog::query()->create([
            'actor_user_id' => $actorUserId,
            'target_user_id' => $targetUserId,
            'event_type' => $eventType,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'reason' => $reason,
            'metadata' => $metadata,
        ]);

        Log::channel('security')->info($eventType, [
            'actor_user_id' => $actorUserId,
            'target_user_id' => $targetUserId,
            'reason' => $reason,
            'metadata' => $metadata,
            'ip' => $request?->ip(),
        ]);

        return $entry;
    }
}
