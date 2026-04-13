<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CgiGeneration extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 
        'user_id', 
        'product_name', 
        'product_image',
        'marketing_angle', 
        'visual_prop', 
        'atmosphere', 
        'camera_motion', 
        'composition', 
        'lighting_style',
        'image_prompt', 
        'video_prompt',
        'audio_prompt',    // Added for audio directives
        'negative_prompt', 
        'status', 
        'image_status', 
        'video_status',
        'image_url',
        'video_url',
        'branded_image_url',
        'branded_video_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A generation can have many social posts.
     */
    public function socialPosts()
    {
        return $this->hasMany(CgiSocialPost::class, 'cgi_generation_id');
    }
}