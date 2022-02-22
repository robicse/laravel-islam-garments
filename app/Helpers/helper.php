<?php
//filter products published
use App\LeaveApplication;
use App\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Current User Details
if (! function_exists('currentUserDetails')) {
    function currentUserDetails($user_id) {
        $user = User::find($user_id);
        $user_role = $user->getRoleNames()[0];
        $warehouse_id = '';
        $warehouse_name = '';
        $store_id = '';
        $store_name = '';

        if(!empty($user->warehouse_id)){
            $warehouse_id = $user->warehouse_id;
            $warehouse_name = \App\Warehouse::where('id',$warehouse_id)->pluck('name')->first();
        }

        if(!empty($user->store_id)){
            $store_id = $user->store_id;
            $store_name = \App\Store::where('id',$store_id)->pluck('name')->first();
        }



        $currentUserDetails = [
            'user_id' => $user_id,
            'user_name' => $user->name,
            'role' => $user_role,
            'warehouse_id' => $warehouse_id,
            'warehouse_name' => $warehouse_name,
            'store_id' => $store_id,
            'store_name' => $store_name,
        ];

        return $currentUserDetails;
    }
}


// dashboard

// total supplier sum
if (! function_exists('totalStaff')) {
    function totalStaff() {
        return DB::table('users')
            ->where('name','!=','production')
            ->where('name','!=','Walk-In-Customer')
            ->get()->count();
    }
}

// total supplier sum
if (! function_exists('totalSupplier')) {
    function totalSupplier() {
        return DB::table('suppliers')
            ->get()->count();
    }
}

// total customer sum
if (! function_exists('totalCustomer')) {
    function totalCustomer() {
        return DB::table('customers')
            ->get()->count();
    }
}

// today purchase sum
if (! function_exists('todayPurchase')) {
    function todayPurchase() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $today_purchase = 0;
        if($role === 'Super Admin'){
            $today_purchase_history = DB::table('product_purchases')
                ->where('purchase_date', date('Y-m-d'))
                ->select(DB::raw('SUM(grand_total_amount) as today_purchase'))
                ->first();
        }else{
            if(!empty($warehouse_id) && !empty($store_id)){
                $today_purchase_history = DB::table('product_purchases')
                    ->where('purchase_date', date('Y-m-d'))
                    ->where('warehouse_id', $warehouse_id)
                    ->where('store_id', $store_id)
                    ->select(DB::raw('SUM(grand_total_amount) as today_purchase'))
                    ->first();
            }elseif(!empty($warehouse_id)){
                $today_purchase_history = DB::table('product_purchases')
                    ->where('purchase_date', date('Y-m-d'))
                    ->where('warehouse_id', $warehouse_id)
                    ->select(DB::raw('SUM(grand_total_amount) as today_purchase'))
                    ->first();
            }elseif(!empty($store_id)){
                $today_purchase_history = DB::table('product_purchases')
                    ->where('purchase_date', date('Y-m-d'))
                    //->where('store_id', $store_id)
                    ->select(DB::raw('SUM(grand_total_amount) as today_purchase'))
                    ->first();
            }else{
                $today_purchase_history = DB::table('product_purchases')
                    ->where('purchase_date', date('Y-m-d'))
                    ->select(DB::raw('SUM(grand_total_amount) as today_purchase'))
                    ->first();
            }

        }

        if(!empty($today_purchase_history)){
            $today_purchase = $today_purchase_history->today_purchase;
        }

        return $today_purchase;
    }
}

// total purchase sum
if (! function_exists('totalPurchase')) {
    function totalPurchase() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $total_purchase = 0;
        if($role === 'Super Admin'){
            $total_purchase_history = DB::table('product_purchases')
                ->select(DB::raw('SUM(grand_total_amount) as total_purchase'))
                ->first();
        }else {
            if (!empty($warehouse_id) && !empty($store_id)) {
                $total_purchase_history = DB::table('product_purchases')
                    ->where('warehouse_id', $warehouse_id)
                    ->where('store_id', $store_id)
                    ->select(DB::raw('SUM(grand_total_amount) as total_purchase'))
                    ->first();
            }elseif(!empty($warehouse_id)){
                $total_purchase_history = DB::table('product_purchases')
                    ->where('warehouse_id', $warehouse_id)
                    ->select(DB::raw('SUM(grand_total_amount) as total_purchase'))
                    ->first();
            }elseif(!empty($store_id)){
                $total_purchase_history = DB::table('product_purchases')
                    //->where('store_id', $store_id)
                    ->select(DB::raw('SUM(grand_total_amount) as total_purchase'))
                    ->first();
            }else{
                $total_purchase_history = DB::table('product_purchases')
                    ->select(DB::raw('SUM(grand_total_amount) as total_purchase'))
                    ->first();
            }
        }

        if(!empty($total_purchase_history)){
            $total_purchase = $total_purchase_history->total_purchase;
        }

        return $total_purchase;
    }
}

// warehouse total current stock
if (! function_exists('warehouseTotalCurrentStock')) {
    function warehouseTotalCurrentStock() {
        $warehouse_current_stock = DB::table('warehouse_current_stocks')
            ->select(DB::raw('SUM(current_stock) as total_current_stock'))
            ->first();

        if($warehouse_current_stock == NULL){
            $warehouse_current_stock = 0;
        }else{
            $warehouse_current_stock = (int) $warehouse_current_stock->total_current_stock;
        }
        return $warehouse_current_stock;
    }
}

// store total current stock
if (! function_exists('storeTotalCurrentStock')) {
    function storeTotalCurrentStock() {
        $store_current_stock = DB::table('warehouse_store_current_stocks')
            ->select(DB::raw('SUM(current_stock) as total_current_stock'))
            ->first();

        if($store_current_stock == NULL){
            $store_current_stock = 0;
        }else{
            $store_current_stock = (int) $store_current_stock->total_current_stock;
        }
        return $store_current_stock;
    }
}

// today sale sum
if (! function_exists('todaySale')) {
    function todaySale() {
        $today_sale = 0;
        $today_sale_history = DB::table('product_sales')
            ->where('sale_date', date('Y-m-d'))
            ->select(DB::raw('SUM(total_amount) as today_sale'),DB::raw('SUM(total_vat_amount) as today_sale_vat_amount'))
            ->first();
        if(!empty($today_sale_history)){
            $today_sale = $today_sale_history->today_sale - $today_sale_history->today_sale_vat_amount;
        }
        return $today_sale;
    }
}

// total sale sum
if (! function_exists('totalSale')) {
    function totalSale() {
        $total_sale = 0;
        $total_sale_history = DB::table('product_sales')
            ->select(DB::raw('SUM(total_amount) as total_sale'),DB::raw('SUM(total_vat_amount) as total_sale_vat_amount'))
            ->first();
        if(!empty($total_sale_history)){
            $total_sale = $total_sale_history->total_sale - $total_sale_history->total_sale_vat_amount;
        }
        return $total_sale;
    }
}

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

// warehouse and product current stock
if (! function_exists('warehouseProductCurrentStock')) {
    function warehouseProductCurrentStock($warehouse_id,$product_id) {
        $warehouse_current_stock = DB::table('warehouse_current_stocks')
            ->where('warehouse_id',$warehouse_id)
            ->where('product_id',$product_id)
            ->latest('id')
            ->pluck('current_stock')
            ->first();

        if($warehouse_current_stock == NULL){
            $warehouse_current_stock = 0;
        }
        return $warehouse_current_stock;
    }
}

// warehouse and product current stock amount
if (! function_exists('warehouseProductCurrentStockAmount')) {
    function warehouseProductCurrentStockAmount($warehouse_id=NULL) {
        $warehouse_current_stocks = DB::table('warehouse_current_stocks')
            ->join('products','warehouse_current_stocks.product_id','products.id')
            //->where('warehouse_id',$warehouse_id)
            ->select('warehouse_current_stocks.current_stock','products.purchase_price')
            ->get();

        $total_amount = 0;
        if(count($warehouse_current_stocks) > 0){
            foreach ($warehouse_current_stocks as $warehouse_current_stock){
                $total_amount += $warehouse_current_stock->current_stock*$warehouse_current_stock->purchase_price;
            }
        }
        return $total_amount;
    }
}

// store and product current stock amount
if (! function_exists('storeProductCurrentStockAmount')) {
    function storeProductCurrentStockAmount($store_id=NULL) {
        $store_current_stocks = DB::table('warehouse_store_current_stocks')
            ->join('products','warehouse_store_current_stocks.product_id','products.id')
            //->where('warehouse_id',$warehouse_id)
            ->select('warehouse_store_current_stocks.current_stock','products.purchase_price')
            ->get();

        $total_amount = 0;
        if(count($store_current_stocks) > 0){
            foreach ($store_current_stocks as $store_current_stock){
                $total_amount += $store_current_stock->current_stock*$store_current_stock->purchase_price;
            }
        }
        return $total_amount;
    }
}

// warehouse wise information
if (! function_exists('warehouseWiseInformation')) {
    function warehouseWiseInformation() {

        $warehouses = DB::table('warehouses')->get();
        $warehouse_arr = [];
        if(count($warehouses) > 0){
            foreach ($warehouses as $warehouse){
                //
                $today_purchase_history = DB::table('product_purchases')
                    ->where('purchase_date', date('Y-m-d'))
                    ->where('warehouse_id', $warehouse->id)
                    ->select(DB::raw('SUM(grand_total_amount) as today_purchase'))
                    ->first();
                if(!empty($total_purchase_history)){
                    $today_purchase_amount = (int) $today_purchase_history->today_purchase;
                }else{
                    $today_purchase_amount = 0;
                }

                //
                $total_purchase_history = DB::table('product_purchases')
                    ->where('warehouse_id',$warehouse->id)
                    ->select(DB::raw('SUM(grand_total_amount) as total_purchase'))
                    ->first();
                if(!empty($total_purchase_history)){
                    $total_purchase_amount = (int) $total_purchase_history->total_purchase;
                }else{
                    $total_purchase_amount = 0;
                }

                //
                $warehouse_current_stocks = DB::table('warehouse_current_stocks')
                    ->join('products','warehouse_current_stocks.product_id','products.id')
                    ->where('warehouse_id',$warehouse->id)
                    ->select('warehouse_current_stocks.current_stock','products.purchase_price')
                    ->get();

                $total_stock = 0;
                $total_stock_amount = 0;
                if(count($warehouse_current_stocks) > 0){
                    foreach ($warehouse_current_stocks as $warehouse_current_stock){
                        $total_stock += $warehouse_current_stock->current_stock;
                        $total_stock_amount += $warehouse_current_stock->current_stock*$warehouse_current_stock->purchase_price;
                    }
                }

                //
                $staff = DB::table('users')
                    ->where('name','!=','production')
                    ->where('name','!=','Walk-In-Customer')
                    ->where('warehouse_id',$warehouse->id)
                    ->get()->count();



                $nested_data['warehouse_name']=$warehouse->name;
                $nested_data['warehouse_staff']=$staff;
                $nested_data['warehouse_today_purchase_amount']=$today_purchase_amount;
                $nested_data['warehouse_total_purchase_amount']=$total_purchase_amount;
                $nested_data['warehouse_current_stock']=$total_stock;
                $nested_data['warehouse_current_stock_amount']=$total_stock_amount;


                array_push($warehouse_arr,$nested_data);
            }
        }

        return $warehouse_arr;
    }
}



// store wise total current stock Amount
if (! function_exists('storeWiseInformation')) {
    function storeWiseInformation() {

        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        //$warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        if($role === 'Super Admin') {
            $stores = DB::table('stores')->get();
        }else{
            $stores = DB::table('stores')->where('id',$store_id)->get();
        }
        $store_arr = [];
        if(count($stores) > 0){
            foreach ($stores as $store){

                $store_current_stocks = DB::table('warehouse_store_current_stocks')
                    ->join('products','warehouse_store_current_stocks.product_id','products.id')
                    ->where('store_id',$store->id)
                    ->select('warehouse_store_current_stocks.current_stock','products.purchase_price')
                    ->get();

                $total_stock = 0;
                $total_stock_amount = 0;
                $today_sale = 0;
                $total_sale = 0;
                if(count($store_current_stocks) > 0){
                    foreach ($store_current_stocks as $store_current_stock){
                        $total_stock += $store_current_stock->current_stock;
                        $total_stock_amount += $store_current_stock->current_stock*$store_current_stock->purchase_price;
                    }
                }

                $today_sale_history = DB::table('product_sales')
                    ->where('sale_date', date('Y-m-d'))
                    ->select(DB::raw('SUM(total_amount) as today_sale'),DB::raw('SUM(total_vat_amount) as today_sale_vat_amount'))
                    ->first();
                if(!empty($today_sale_history)){
                    $today_sale = $today_sale_history->today_sale - $today_sale_history->today_sale_vat_amount;
                }

                $total_sale_history = DB::table('product_sales')
                    ->select(DB::raw('SUM(total_amount) as total_sale'),DB::raw('SUM(total_vat_amount) as total_sale_vat_amount'))
                    ->first();
                if(!empty($total_sale_history)){
                    $total_sale = $total_sale_history->total_sale - $total_sale_history->total_sale_vat_amount;
                }

                $staff = DB::table('users')
                    ->where('name','!=','production')
                    ->where('name','!=','Walk-In-Customer')
                    ->where('store_id',$store->id)
                    ->get()->count();

                $nested_data['store_name']=$store->name;
                $nested_data['store_staff']=$staff;
                $nested_data['store_today_sale_amount']=$today_sale;
                $nested_data['store_total_sale_amount']=$total_sale;
                $nested_data['store_current_stock']=$total_stock;
                $nested_data['store_current_stock_amount']=$total_stock_amount;

                array_push($store_arr,$nested_data);
            }
        }

        return $store_arr;
    }
}


//if (! function_exists('checkExistsProduct')) {
//    function checkExistsProduct($type,$product_category_id,$product_size_id,$product_unit_id,$product_sub_unit_id=NULL,$product_code=NULL) {
//
//        if( (!empty($product_sub_unit_id)) && (!empty($product_code !== '')) ){
//            $check_exists_product = DB::table("products")
//                ->where('type',$type)
//                ->where('product_category_id',$product_category_id)
//                ->where('product_size_id',$product_size_id)
//                ->where('product_unit_id',$product_unit_id)
//                ->where('product_sub_unit_id',$product_sub_unit_id)
//                ->where('product_code',$product_code)
//                ->pluck('id')->first();
//        }elseif( (!empty($product_sub_unit_id)) && (empty($product_code !== '')) ){
//            $check_exists_product = DB::table("products")
//                ->where('type',$type)
//                ->where('product_category_id',$product_category_id)
//                ->where('product_size_id',$product_size_id)
//                ->where('product_unit_id',$product_unit_id)
//                ->where('product_sub_unit_id',$product_sub_unit_id)
//                ->pluck('id')->first();
//        }elseif( (empty($product_sub_unit_id)) && (!empty($product_code !== '')) ){
//            $check_exists_product = DB::table("products")
//                ->where('type',$type)
//                ->where('product_category_id',$product_category_id)
//                ->where('product_size_id',$product_size_id)
//                ->where('product_unit_id',$product_unit_id)
//                ->where('product_code',$product_code)
//                ->pluck('id')->first();
//        }else{
//            $check_exists_product = DB::table("products")
//                ->where('type',$type)
//                ->where('product_category_id',$product_category_id)
//                ->where('product_size_id',$product_size_id)
//                ->where('product_unit_id',$product_unit_id)
//                ->pluck('id')->first();
//        }
//
//        return $check_exists_product;
//    }
//}
