<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderEdk extends Model
{
    use SoftDeletes;

    public const STATUS_LANJUT = 'lanjut';

    public const STATUS_TIDAK_LANJUT = 'tidak_lanjut';

    public const STATUS_BELUM_INPUT = 'belum_input';

    public const STATUS_OGP = 'ogp';

    public const STATUS_COMPLETE = 'complete';

    public const FINAL_STATUSES = [
        self::STATUS_TIDAK_LANJUT,
        self::STATUS_COMPLETE,
    ];

    public const LABELS = [
        self::STATUS_LANJUT => 'Lanjut',
        self::STATUS_TIDAK_LANJUT => 'Tidak Lanjut',
        self::STATUS_BELUM_INPUT => 'Belum Input',
        self::STATUS_OGP => 'OGP',
        self::STATUS_COMPLETE => 'Complete',
    ];

    protected $fillable = [
        'edk_reference',
        'customer_name',
        'inputer_id',
        'account_manager_id',
        'status',
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
