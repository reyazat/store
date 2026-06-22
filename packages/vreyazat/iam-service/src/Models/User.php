<?php

namespace IamApi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int id
 * @property string uuid
 * @property int organization_id
 * @property string name
 * @property string email
 * @property string phone
 * @property string password
 * @property boolean must_change_password
 * @property boolean mfa_enabled
 * @property boolean is_super_admin
 * @property string status
 * @property string last_login_ip
 * @property UserTotpSecret totpSecret
 * @property UserRecoveryCode recoveryCodes
 * @property Organization organization
 * @property int failed_login_attempts
 * @property Carbon last_login_at
 * @property Carbon password_changed_at
 * @property Carbon password_expires_at
 * @property Carbon locked_until
 * @property Carbon email_verified_at
 * @property Carbon phone_verified_at
 * @property Carbon created_at
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected ?string $guard_name = 'api';

    protected $fillable = [
        'name','email','phone','password','organization_id',
        'must_change_password','mfa_enabled','is_super_admin','password_changed_at','password_expires_at',
        'status','locked_until','failed_login_attempts','last_login_at','last_login_ip',
        'email_verified_at','phone_verified_at',
    ];

    protected $hidden = ['password'];
    protected $casts = [
        'email_verified_at'   => 'datetime',
        'phone_verified_at'   => 'datetime',
        'must_change_password'=> 'bool',
        'mfa_enabled'         => 'bool',
        'is_super_admin'      => 'bool',
        'password_changed_at' => 'datetime',
        'password_expires_at' => 'datetime',
        'locked_until'        => 'datetime',
        'last_login_at'       => 'datetime',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // JWT
    public function getJWTIdentifier(){ return $this->getKey(); }
    public function getJWTCustomClaims(): array
    {
        // organization_id will be added dynamically in TokenService
        return [];
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'email',
                'phone',
                'status',
                'mfa_enabled',
                'last_login_at',
                'email_verified_at',
                'phone_verified_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "User model has been $eventName");

    }

    // Relations
    public function jwtTokens(): HasMany
    { return $this->hasMany(JwtToken::class, 'user_id', 'id'); }
    public function otps(): HasMany
    { return $this->hasMany(Otp::class, 'user_id', 'id'); }
    public function totpSecret(): HasOne
    { return $this->hasOne(UserTotpSecret::class, 'user_id', 'id'); }
    public function recoveryCodes(): HasMany
    { return $this->hasMany(UserRecoveryCode::class, 'user_id', 'id'); }
    public function passwordHistories(): HasMany
    { return $this->hasMany(PasswordHistory::class, 'user_id', 'id'); }
    public function verificationTokens(): HasMany
    { return $this->hasMany(VerificationToken::class, 'user_id', 'id'); }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function setPasswordAttribute($value): void
    {
        if ($value && !str_starts_with((string)$value, '$2y$') && !str_starts_with((string)$value, '$argon2')){
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    public function isLocked(): bool {
        return $this->locked_until && now()->lt($this->locked_until);
    }

    public function hasMfaEnabled(): bool {
        return $this->mfa_enabled;
    }

    public function hasTotpConfigured(): bool {
        return $this->totpSecret()->exists();
    }

    public function hasRecoveryCodes(): bool {
        return $this->recoveryCodes()->where('used_at', null)->exists();
    }

    public function isSuperAdmin(): bool {
        return $this->is_super_admin === true;
    }

    /**
     * A buyer is a marketplace customer: authenticated but not assigned to any store organization.
     * Super admins and store members are never considered buyers.
     */
    public function isBuyer(): bool {
        return !$this->is_super_admin && $this->organization_id === null;
    }

    public function isOwnerOf(Organization $organization): bool {
        // Super admin can own any organization
        if ($this->is_super_admin) {
            return true;
        }

        // User must belong to the organization
        if ($this->organization_id !== $organization->id) {
            return false;
        }

        // Check if user has owner role for this organization
        // The team context should already be set by SetOrganizationContext middleware
        // But ensure it's set to this organization
        $currentTeamId = app(PermissionRegistrar::class)->getPermissionsTeamId();

        if ($currentTeamId !== $organization->id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        }

        // Check if user has owner role
        return $this->hasRole('owner');
    }

    public function belongsToOrganization(Organization $organization): bool {
        return $this->organization_id === $organization->id;
    }
}
