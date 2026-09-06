<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCashReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'beginning_cash',
        'retail_rice_sales',
        'wholesale_rice_sales',
        'system_grocery_sales',
        'manual_grocery_sales',
        'total_expected_sales',
        'payout_expenses',
        'expected_cash_in_drawer',
        'actual_cash_counted',
        'cash_variance',
        'notes',
        'user_id',
    ];
}