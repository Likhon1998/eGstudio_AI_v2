<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CgiSocialPost extends Model
{
    use HasFactory;

    // The columns we are allowed to save data to
    protected $fillable = [
        'cgi_generation_id',
        'platform',
        'media_type',
        'is_branded',
        'media_url',
        'caption',
        'status',
        'published_at',
    ];

    // Ensures Laravel treats these columns correctly (e.g., true/false instead of 1/0)
    protected $casts = [
        'is_branded' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Relationship: Connects this post back to the original generation (UUID).
     */
    public function generation()
    {
        return $this->belongsTo(CgiGeneration::class, 'cgi_generation_id');
    }
}