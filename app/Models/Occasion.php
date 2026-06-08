<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Occasion extends Model
{
    use HasFactory;

    // Match CGI Generation UUID structure
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 
        'user_id', 
        
        // --- NEW FIELDS ADDED ---
        'target_month',
        'target_year',
        'custom_text_payload',
        // ------------------------

        'occasion_identity', 
        'visual_direction',
        'custom_text', 
        'camera_motion', 
        'motion_intensity', 
        'image_prompt', 
        'video_prompt',
        'audio_prompt',
        'negative_prompt', 
        'status',
        'prompt_error_message',
        'prompt_credit_deducted',
        'image_status', 
        'video_status',
        'image_url',
        'video_url',
        'branded_image_url',
        'branded_video_url',
        'merged_image_url',
        'merge_status',
        'branding_logo_credit_deducted',
        'merge_branding_credit_deducted',
    ];

    /**
     * Automatically generate a UUID when creating a new Occasion campaign.
     */
    protected $casts = [
        'prompt_credit_deducted'          => 'boolean',
        'branding_logo_credit_deducted'   => 'boolean',
        'merge_branding_credit_deducted'  => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialPosts()
    {
        return $this->hasMany(OccasionSocialPost::class, 'occasion_id');
    }
}