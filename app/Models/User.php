<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // 1. IMPORT SPATIE TRAIT

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles; // 2. ADD 'HasRoles' HERE

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Added for role-based access control
        
        // --- ADDED FOR SAAS BILLING ---
        'package_id',
        'directive_credits',
        'image_credits',
        'video_credits',
        'branding_credits',
        'branding_image_credits',
        'branding_video_credits',
        'social_post_credits',
        'expiry_date', // <-- ADDED THIS so it can be updated by the Admin
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
            'password' => 'hashed',
            'expiry_date' => 'datetime', // <-- ADDED THIS so Laravel treats it as a Date
        ];
    }

    /**
     * RELATIONSHIP: A User belongs to a specific Package
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
    /**
     * Get the logos associated with the user.
     */
    public function logos()
    {
        return $this->hasMany(Logo::class);
    }
}