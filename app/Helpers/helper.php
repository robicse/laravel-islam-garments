<?php
//filter products published
use App\LeaveApplication;
use App\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// user name as id
if (! function_exists('userName')) {
    function userName($user_id) {
        return DB::table('users')
            ->where('id',$user_id)
            ->pluck('name')
            ->first();
    }
}

// warehouse name as id
if (! function_exists('warehouseName')) {
    function warehouseName($warehouse_id) {
        return DB::table('warehouses')
            ->where('id',$warehouse_id)
            ->pluck('name')
            ->first();
    }
}

// supplier name as id
if (! function_exists('supplierName')) {
    function supplierName($supplier_id) {
        return DB::table('suppliers')
            ->where('id',$supplier_id)
            ->pluck('name')
            ->first();
    }
}

// payment type
if (! function_exists('paymentType')) {
    function paymentType($id) {
        return DB::table('payment_types')
            ->where('id',$id)
            ->pluck('name')
            ->first();
    }
}





