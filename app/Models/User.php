<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, HasRoles, Notifiable;

    /**
     * Only superadmins and users with the admin role may access the
     * Filament admin panel. Customers never can.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_superadmin || $this->hasRole('admin');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_superadmin',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
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

    /**
     * Check if this user is a system super admin (platform-level, no company dependency).
     */
    public function isSystemSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if this user is a company admin (can manage staff roles/permissions within their company).
     */
    public function isCompanyAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Determine if the user has unrestricted platform-level access.
     * Backwards-compatible alias for isSystemSuperAdmin().
     */
    public function isSuperAdmin(): bool
    {
        return $this->isSystemSuperAdmin();
    }

    /**
     * Get the customer profiles associated with this user.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the primary customer profile for this user.
     */
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get quotations created by this user.
     */
    public function quotationsCreated()
    {
        return $this->hasMany(Quotation::class, 'created_by');
    }
}
