<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'price', 'description'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }


}
