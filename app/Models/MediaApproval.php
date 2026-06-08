<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaApproval extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'cgi_generation_id',
        'source',
        'maker_id',
        'product_name',
        'media_type',
        'variant',
        'is_branded',
        'media_url',
        'status',
        'comment',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'is_branded'  => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function generation()
    {
        return $this->belongsTo(CgiGeneration::class, 'cgi_generation_id');
    }

    public function maker()
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
