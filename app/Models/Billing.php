<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    // Allow Laravel to save all of these columns during mass assignment
    protected $fillable = [
        'user_id',
        'package_id',
        'invoice_no',
        'amount',
        'status',
        'paid_at',
        'transaction_id', 
        'payment_proof',  
    ];

    // Ensure dates are cast correctly so the Blade views can format them without errors
    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}