<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SaleItem;

class Sale extends Model
{
     protected $fillable = ['branch_id','user_id','invoice_no','sale_date','total_amount'];

    protected $casts = [
        'sale_date' => 'datetime',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function items() {
        return $this->hasMany(SaleItem::class);
    }
}
