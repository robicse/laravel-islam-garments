<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OverallCost extends Model
{
    public function overallCostCategory(){
        return $this->belongsTo(OverallCostCategory::class);
    }
}
