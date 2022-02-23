<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountTransaction extends Model
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

    public function payment_type()
    {
        return $this->belongsTo('App\PaymentType', 'payment_type_id');
    }

    public function voucher_type()
    {
        return $this->belongsTo('App\VoucherType', 'voucher_type_id');
    }
}
