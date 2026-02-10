<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Stock;

class Branch extends Model
{
        protected $fillable = ['company_id','name','address','phone'];



    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function users() {
        return $this->hasMany(User::class);
    }

    public function stocks() {
        return $this->hasMany(Stock::class);
    }

}
