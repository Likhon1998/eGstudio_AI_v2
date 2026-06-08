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

        // --- ADDED FOR APPROVAL WORKFLOW ---
        'account_type', // 'approver' | null (standard user)
        'client_id',    // groups the 2 credentials of a single client

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

    /**
     * Relationship: A user can generate many Occasion Campaigns.
     */
    public function occasions()
    {
        return $this->hasMany(Occasion::class);
    }

    // Inside app/Models/User.php
    public function cgiGenerations()
    {
        return $this->hasMany(CgiGeneration::class);
    }

    /**
     * =====================================================================
     * APPROVAL WORKFLOW HELPERS
     * =====================================================================
     */

    public function isApprover(): bool
    {
        return $this->account_type === 'approver';
    }

    public function isAdmin(): bool
    {
        if (!$this->role) {
            return false;
        }

        return in_array(strtolower($this->role), ['admin', 'super admin', 'system admin'], true);
    }

    /**
     * The approver account assigned to this user/client (if any).
     */
    public function approver()
    {
        return $this->hasOne(User::class, 'client_id', 'id')
            ->where('account_type', 'approver');
    }

    /**
     * The user (client) this approver reviews content for.
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * The user id whose content this account is tied to (the "client").
     * For an approver it's their linked user; otherwise it's itself.
     */
    public function clientOwnerId()
    {
        return $this->isApprover() ? $this->client_id : $this->id;
    }

    /**
     * Standard users with an approver must get merged pictures/videos approved before publish.
     * Admins and approver accounts are never in this workflow.
     */
    public function requiresApproval(): bool
    {
        return !$this->isAdmin()
            && !$this->isApprover()
            && $this->approver()->exists();
    }

    public function mediaApprovals()
    {
        return $this->hasMany(MediaApproval::class, 'maker_id');
    }
}