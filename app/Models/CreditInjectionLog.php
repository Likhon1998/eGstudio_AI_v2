<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditInjectionLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Gets the Client's info
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Gets the Admin's info
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}