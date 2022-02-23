<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductSubUnit extends Model
{
    public function unit()
    {
        return $this->belongsTo('App\ProductUnit', 'product_unit_id');
    }
}
