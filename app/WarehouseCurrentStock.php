<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WarehouseCurrentStock extends Model
{
    public function warehouse()
    {
        return $this->belongsTo('App\Warehouse', 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id');
    }
}
