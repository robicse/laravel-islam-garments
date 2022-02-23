<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Warehouse', 'warehouse_id');
    }

    public function store()
    {
        return $this->belongsTo('App\Store', 'store_id');
    }
}
