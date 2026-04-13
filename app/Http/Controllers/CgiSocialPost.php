<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CgiSocialPost extends Model
{
    protected $guarded = [];

    // Link back to the generation
    public function generation()
    {
        return $this->belongsTo(CgiGeneration::class, 'cgi_generation_id');
    }
}