<?php

namespace Workbench\App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Namu\WireChat\Traits\Chatable;

class Admin extends Authenticatable
{
    use Chatable;
    use HasFactory, Notifiable;

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
        return \Namu\WireChat\Workbench\Database\Factories\AdminFactory::new();
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
}
