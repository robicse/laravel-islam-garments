<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
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
