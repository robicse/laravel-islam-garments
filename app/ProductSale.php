<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductSale extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id');
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
