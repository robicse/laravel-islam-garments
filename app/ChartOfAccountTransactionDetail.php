<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountTransactionDetail extends Model
{
    public function warehouse()
    {
        return $this->belongsTo('App\Warehouse', 'warehouse_id');
    }

    public function store()
    {
        return $this->belongsTo('App\Store', 'store_id');
    }

    public function payment_type()
    {
        return $this->belongsTo('App\PaymentType', 'payment_type_id');
    }
}
