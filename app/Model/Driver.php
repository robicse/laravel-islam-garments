<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    public function vehicle(){
        return $this->belongsTo(Vehicle::class);
    }
}
