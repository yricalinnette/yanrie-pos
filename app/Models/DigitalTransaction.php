<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'amount',
        'service_fee',
        'cash_on_hand_change',
        'gcash_balance_change',
        'reference_number',
        'customer_phone',
        'is_active',
        'logged_at',
    ];
}