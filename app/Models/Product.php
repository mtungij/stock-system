<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
       protected $fillable = [
        'name',
        'category_id',
        'unit',
        'min_stock'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

public function getQuantityAttribute()
{
    return $this->stock->quantity ?? 0;
}

}
