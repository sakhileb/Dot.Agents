<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'consent_records' => 'array',
        'erased_at' => 'datetime',
    ];

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'department', 'job_title', 'is_primary', 'joined_at'])
            ->withTimestamps();
    }

    public function currentOrganization(): ?Organization
    {
        return $this->organizations()->wherePivot('is_primary', true)->first()
            ?? $this->organizations()->first();
    }

    public function platformNotifications()
    {
        return $this->hasMany(PlatformNotification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->platformNotifications()->whereNull('read_at')->count();
    }

    public function pendingApprovals()
    {
        return AgentApproval::where('requested_from', $this->id)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
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
            'password' => 'hashed',
        ];
    }
}
