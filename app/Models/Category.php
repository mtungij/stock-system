<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Company;

class Category extends Model
{
   protected $fillable = ['name','description','company_id'];

    public function products() {
        return $this->hasMany(Product::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }
}
