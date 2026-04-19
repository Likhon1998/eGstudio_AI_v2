<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logo extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'file_path'];

    // Optional: Helper to get the full URL whether local or Cloudinary
    public function getUrlAttribute()
    {
        // If you are using Cloudinary, it will likely be a full HTTP URL already
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }
        return asset('storage/' . $this->file_path);
    }
}