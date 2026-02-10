<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Purchase;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'address'];

    public function purchases() {
        return $this->hasMany(Purchase::class);
    }
}
