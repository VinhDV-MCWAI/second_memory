<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Enums\AuditEvent;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail (ADR-0006).
 *
 * @property AuditEvent $event
 * @property-read AdminMst|null $actor
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_log';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'admin_mst_id',
        'ip_address',
        'created_at',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(AdminMst::class, 'admin_mst_id');
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'auditable_id' => 'integer',
            'event' => AuditEvent::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'admin_mst_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
