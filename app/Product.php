<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function category()
    {
        return $this->belongsTo('App\ProductCategory', 'product_category_id');
    }

    public function size()
    {
        return $this->belongsTo('App\ProductSize', 'product_size_id');
    }

    public function unit()
    {
        return $this->belongsTo('App\ProductUnit', 'product_unit_id');
    }

    public function sub_unit()
    {
        return $this->belongsTo('App\ProductSubUnit', 'product_sub_unit_id');
    }
}
