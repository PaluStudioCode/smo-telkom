<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompletionRecord extends Model
{
    use SoftDeletes;

    public const STATUS_MENUNGGU_PERSETUJUAN = 'menunggu_persetujuan';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_TIDAK_DISETUJUI = 'tidak_disetujui';

    public const STATUS_REVISI = 'revisi';

    public const FINAL_STATUSES = [
        self::STATUS_DISETUJUI,
    ];

    public const LABELS = [
        self::STATUS_MENUNGGU_PERSETUJUAN => 'Menunggu Persetujuan',
        self::STATUS_DISETUJUI => 'Disetujui',
        self::STATUS_TIDAK_DISETUJUI => 'Tidak Disetujui',
        self::STATUS_REVISI => 'Revisi',
    ];

    protected $fillable = [
        'completion_number',
        'order_status_id',
        'order_edk_id',
        'inputer_id',
        'account_manager_id',
        'approval_status',
        'completed_at',
        'approved_by',
        'approved_at',
        'revision_note',
        'period_month',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function orderEdk(): BelongsTo
    {
        return $this->belongsTo(OrderEdk::class);
    }

    public function inputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inputer_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_DISETUJUI;
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
