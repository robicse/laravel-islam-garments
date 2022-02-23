<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Supplier', 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Warehouse', 'warehouse_id');
    }

    public function payment_type()
    {
        return $this->belongsTo('App\PaymentType', 'payment_type_id');
    }
}
