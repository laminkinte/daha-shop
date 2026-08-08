<?php

namespace App\Models;

use App\Enums\AdminPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_email',
        'target_id',
        'target_name',
        'target_email',
        'action',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function summary(): string
    {
        $labels = fn (array $values) => implode(', ', array_map(
            fn (string $value) => AdminPermission::from($value)->label(),
            $values
        )) ?: 'none';

        return match ($this->action) {
            'created' => 'Created with permissions: '.$labels($this->changes['permissions'] ?? []),
            'granted' => 'Granted admin access with permissions: '.$labels($this->changes['permissions'] ?? []),
            'promoted' => 'Promoted to Super Admin',
            'demoted' => 'Demoted to scoped admin ('.$labels($this->changes['after']['admin_permissions'] ?? []).')',
            'permissions_updated' => 'Permissions changed to: '.$labels($this->changes['after']['admin_permissions'] ?? []),
            'revoked' => 'Admin access revoked',
            'reinstated' => ($this->changes['is_super_admin'] ?? false)
                ? 'Reinstated as Super Admin'
                : 'Reinstated with permissions: '.$labels($this->changes['restored_permissions'] ?? []),
            'blocked' => 'Account blocked',
            'unblocked' => 'Account unblocked',
            'deleted' => 'Admin account deleted (access fully revoked and blocked)',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
