<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['supplier_id','branch_id','invoice_no','purchase_date','total_amount'];

    protected $casts = [
        'purchase_date' => 'datetime',
    ];

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function items() {
        return $this->hasMany(PurchaseItem::class);
    }
}
