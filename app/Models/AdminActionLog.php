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
            'promoted' => 'Promoted to Super Admin',
            'demoted' => 'Demoted to scoped admin ('.$labels($this->changes['after']['admin_permissions'] ?? []).')',
            'permissions_updated' => 'Permissions changed to: '.$labels($this->changes['after']['admin_permissions'] ?? []),
            'revoked' => 'Admin access revoked',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
