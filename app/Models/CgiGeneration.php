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
        'video_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}