<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatus extends Model
{
    use SoftDeletes;

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_PENDING_BASO = 'pending_baso';

    public const STATUS_PENDING_BILLING_APPROVAL = 'pending_billing_approval';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCEL_ABANDONED = 'cancel_abandoned';

    public const FINAL_STATUSES = [
        self::STATUS_COMPLETE,
        self::STATUS_FAILED,
        self::STATUS_CANCEL_ABANDONED,
    ];

    public const LABELS = [
        self::STATUS_PROVISIONING => 'Provisioning',
        self::STATUS_PENDING_BASO => 'Pending BASO',
        self::STATUS_PENDING_BILLING_APPROVAL => 'Pending Billing Approval',
        self::STATUS_COMPLETE => 'Complete',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_CANCEL_ABANDONED => 'Cancel / Abandoned',
    ];

    protected $fillable = [
        'order_number',
        'customer_name',
        'service_name',
        'inputer_id',
        'account_manager_id',
        'status',
        'provisioning_stage',
        'period_month',
        'source_system',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function inputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inputer_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return array_keys(self::LABELS);
    }

    public function isFinalStatus(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            User::ROLE_ADMIN_INPUTER => $query->where('inputer_id', $user->id),
            User::ROLE_ACCOUNT_MANAGER => $query->where('account_manager_id', $user->id),
            default => $query,
        };
    }
}
