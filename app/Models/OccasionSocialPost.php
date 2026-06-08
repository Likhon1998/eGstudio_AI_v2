<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OccasionSocialPost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_branded'   => 'boolean',
        'scheduled_at' => 'datetime',
    ];

    // Relationship to the Occasion
    public function occasion()
    {
        return $this->belongsTo(Occasion::class);
    }

    // Relationship to the User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}