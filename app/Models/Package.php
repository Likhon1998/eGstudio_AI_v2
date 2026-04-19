<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'billing_cycle',
        'stripe_product_id',
        'directive_allowance',
        'image_allowance',
        'video_allowance',
        'branding_allowance',
        'branding_image_allowance',
        'branding_video_allowance',
        'social_post_allowance'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}