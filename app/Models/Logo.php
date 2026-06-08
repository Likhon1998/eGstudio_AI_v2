<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logo extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'file_path'];

    public function getUrlAttribute(): string
    {
        return PublicMediaUrl::forPath($this->file_path);
    }

    public function fileExistsOnDisk(): bool
    {
        return PublicMediaUrl::exists($this->file_path);
    }
}