<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Model;

class ProductAsset extends Model
{
    public const TYPE_PRODUCT = 'product';

    public const TYPE_TEMPLATE = 'template';

    protected $fillable = [
        'user_id',
        'asset_type',
        'name',
        'file_path',
    ];

    protected $attributes = [
        'asset_type' => self::TYPE_PRODUCT,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeProducts($query)
    {
        return $query->where('asset_type', self::TYPE_PRODUCT);
    }

    public function scopeTemplates($query)
    {
        return $query->where('asset_type', self::TYPE_TEMPLATE);
    }

    public function isTemplate(): bool
    {
        return $this->asset_type === self::TYPE_TEMPLATE;
    }

    public function getPublicUrlAttribute(): string
    {
        return $this->url;
    }

    public function getUrlAttribute(): string
    {
        return PublicMediaUrl::forPath($this->file_path);
    }

    public function fileExistsOnDisk(): bool
    {
        return PublicMediaUrl::exists($this->file_path);
    }
}