<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPackage extends Model
{
    use HasFactory;

    // Define the exact table name
    protected $table = 'user_packages';

    // THIS IS THE IMPORTANT PART! Allow these columns to be saved!
    protected $fillable = [
        'user_id',
        'package_id',
        'is_active_selection',
        'directive_credits',
        'image_credits',
        'video_credits',
        'branding_credits',
        'social_post_credits',
        'expires_at',
    ];

    // Ensure dates are cast properly
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}