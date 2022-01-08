<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];
    public function payment_type(){
        return $this->belongsTo(PaymentType::class);
    }
    public function order(){
        return $this->belongsTo(Order::class);
    }
}
