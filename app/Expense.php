<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public function expense_category(){
        return $this->belongsTo('App\ExpenseCategory','expense_category_id');
    }

    public function store(){
        return $this->belongsTo('App\Store','store_id');
    }

    public function warehouse(){
        return $this->belongsTo('App\Warehouse','warehouse_id');
    }
}
