<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'stock_id',
        'user_id',
        'adjustment_type',
        'action',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reason',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
