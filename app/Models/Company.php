<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Branch;

class Company extends Model
{
      protected $fillable = ['name','email','phone','address'];

        public function branches() {
        return $this->hasMany(Branch::class);
    }
}
