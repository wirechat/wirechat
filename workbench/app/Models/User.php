<?php

namespace Workbench\App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Wirechat\Wirechat\Contracts\WirechatUser;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\Traits\Chatable;
use Wirechat\Wirechat\Traits\InteractsWithWirechat;

class User extends Authenticatable implements WirechatUser
{
    use HasFactory, Notifiable;

    // use Chatable;
    use InteractsWithWirechat;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Wirechat\Wirechat\Workbench\Database\Factories\UserFactory::new();
    }

    public function getCoverUrlAttribute(): ?string
    {

        return null;
    }

    public function wireChatProfileUrl(): ?string
    {
        return null;
    }

    public function getDisplayNameAttribute(): ?string
    {

        return $this->name ?? 'user';

    }

    public function canCreateGroups(): bool
    {
        return $this->hasVerifiedEmail() == true;
    }

    public function canCreateChats(): bool
    {
        return $this->hasVerifiedEmail() == true;
    }

    public function canAccessWirechatPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail();

    }
}
