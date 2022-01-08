<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function vendor(){
        return $this->belongsTo(Vendor::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(){
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(){
        return $this->belongsTo(Driver::class);
    }

    public function payment_type(){
        return $this->belongsTo(PaymentType::class);
    }

    public function order_item(){
        return $this->belongsTo(OrderItem::class);
    }
}
