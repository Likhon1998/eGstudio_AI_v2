<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAsset extends Model
{
    protected $fillable = [
        'user_id', 
        'name', 
        'file_path'
    ];

    // Links the asset back to the user who uploaded it
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}