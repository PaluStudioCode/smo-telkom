<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN_INPUTER = 'admin_inputer';

    public const ROLE_ACCOUNT_MANAGER = 'account_manager';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'profile_photo_path',
        'bio',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdminInputer(): bool
    {
        return $this->role === self::ROLE_ADMIN_INPUTER;
    }

    public function isAccountManager(): bool
    {
        return $this->role === self::ROLE_ACCOUNT_MANAGER;
    }

    public function orderStatusesAsInputer(): HasMany
    {
        return $this->hasMany(OrderStatus::class, 'inputer_id');
    }

    public function orderStatusesAsAccountManager(): HasMany
    {
        return $this->hasMany(OrderStatus::class, 'account_manager_id');
    }

    public function orderEdksAsInputer(): HasMany
    {
        return $this->hasMany(OrderEdk::class, 'inputer_id');
    }

    public function orderEdksAsAccountManager(): HasMany
    {
        return $this->hasMany(OrderEdk::class, 'account_manager_id');
    }

    public function completionRecordsAsInputer(): HasMany
    {
        return $this->hasMany(CompletionRecord::class, 'inputer_id');
    }

    public function completionRecordsAsAccountManager(): HasMany
    {
        return $this->hasMany(CompletionRecord::class, 'account_manager_id');
    }

    public function hasOperationalRecords(): bool
    {
        return $this->orderStatusesAsInputer()->exists()
            || $this->orderStatusesAsAccountManager()->exists()
            || $this->orderEdksAsInputer()->exists()
            || $this->orderEdksAsAccountManager()->exists()
            || $this->completionRecordsAsInputer()->exists()
            || $this->completionRecordsAsAccountManager()->exists();
    }
}
