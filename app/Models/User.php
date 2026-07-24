<?php

namespace App\Models;

use App\Enums\AdminPermission;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'role',
        'password',
        'uses_pin',
        'is_super_admin',
        'admin_permissions',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'uses_pin' => 'boolean',
            'is_super_admin' => 'boolean',
            'admin_permissions' => 'array',
        ];
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function deliveryAgent(): HasOne
    {
        return $this->hasOne(DeliveryAgent::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isVendor(): bool
    {
        return $this->role === UserRole::Vendor;
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->is_super_admin;
    }

    public function hasAdminPermission(AdminPermission $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isAdmin() && in_array($permission->value, $this->admin_permissions ?? [], true);
    }

    public function isPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Phone/PIN accounts store a synthetic `xxx@phone.dahashop.internal`
     * address in the email column (see register.blade.php) since they never
     * gave a real one - that address must never actually be emailed.
     */
    public function hasRealEmail(): bool
    {
        return $this->email && ! $this->uses_pin;
    }

    /**
     * Phone/PIN accounts can never receive a verification email (their
     * address is a synthetic placeholder) - they're verified via phone OTP
     * instead (see isPhoneVerified()), so they're always considered
     * "email verified" for the purposes of the `verified` middleware gate.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->uses_pin || $this->email_verified_at !== null;
    }
}
