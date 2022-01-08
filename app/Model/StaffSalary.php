<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StaffSalary extends Model
{
    public function user(){
        return $this->belongsTo('App\User','user_id');
    }

    public function payment_type(){
        return $this->belongsTo('App\Model\PaymentType','payment_type_id');
    }
}
