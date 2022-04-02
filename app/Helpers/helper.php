<?php
//filter products published
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\LeaveApplication;
use App\Product;
use App\Stock;
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
            ->where('name','!=','Super Admin')
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
        $today_purchase_history = \App\ProductPurchase::select(DB::raw('SUM(grand_total_amount) as today_purchase'))
            ->where('product_purchases.purchase_date',date('Y-m-d'));

        if($role !== 'Super Admin'){
            $today_purchase_history->where('product_purchases.warehouse_id',$warehouse_id);
        }
        $today_purchase_data = $today_purchase_history->first();
        if(!empty($today_purchase_data)){
            $today_purchase = $today_purchase_data->today_purchase;
        }


//        $today_purchase_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(debit) as today_purchase'))
//            ->where('chart_of_account_transaction_details.chart_of_account_name','Inventory')
//            ->where('chart_of_account_transaction_details.transaction_type','Purchases')
//            ->where('chart_of_account_transaction_details.transaction_date',date('Y-m-d'));
//
//        if($role !== 'Super Admin'){
//            $today_purchase_history->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
//            $today_purchase_history->where('chart_of_account_transaction_details.store_id',$store_id);
//        }
//        $today_purchase_data = $today_purchase_history->first();
//        if(!empty($today_purchase_data)){
//            $today_purchase = $today_purchase_data->today_purchase;
//        }

        return $today_purchase;
    }
}

// today cash purchase sum
if (! function_exists('todayCashPurchase')) {
    function todayCashPurchase() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $today_cash_purchase = 0;
        $today_cash_purchase_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(credit) as today_cash_purchase'))
            ->where('chart_of_account_transaction_details.chart_of_account_name','Cash In Hand')
            ->where('chart_of_account_transaction_details.transaction_type','Purchases')
            ->where('chart_of_account_transaction_details.transaction_date',date('Y-m-d'));

        if($role !== 'Super Admin'){
            $today_cash_purchase_history->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }
        $today_cash_purchase_data = $today_cash_purchase_history->first();
        if(!empty($today_cash_purchase_data)){
            $today_cash_purchase = $today_cash_purchase_data->today_cash_purchase;
        }

        return $today_cash_purchase;
    }
}

// total purchase sum
if (! function_exists('totalPurchase')) {
    function totalPurchase() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];


        $this_year = date('Y');
        $this_month = date('m');
        $today = date('d');
        $from_date_this_month = $this_year.'-'.$this_month.'-01';
        $current_date_this_month = $this_year.'-'.$this_month.'-'.$today;


        $total_purchase = 0;
        $total_purchase_history = \App\ProductPurchase::select(DB::raw('SUM(grand_total_amount) as total_purchase'));

        $total_purchase_history->whereBetween('product_purchases.purchase_date',[$from_date_this_month, $current_date_this_month]);

        if($role !== 'Super Admin'){
            $total_purchase_history->where('product_purchases.warehouse_id',$warehouse_id);
        }
        $total_purchase_data = $total_purchase_history->first();
        if(!empty($total_purchase_data)){
            $total_purchase = $total_purchase_data->total_purchase;
        }




//        $total_purchase_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(debit) as total_purchase'))
//            ->where('chart_of_account_transaction_details.chart_of_account_name','Inventory')
//            ->where('chart_of_account_transaction_details.transaction_type','Purchases');
//
//        if($role !== 'Super Admin'){
//            $total_purchase_history->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
//            $total_purchase_history->where('chart_of_account_transaction_details.store_id',$store_id);
//        }
//        $total_purchase_data = $total_purchase_history->first();
//        if(!empty($total_purchase_data)){
//            $total_purchase = $total_purchase_data->total_purchase;
//        }

        return $total_purchase;
    }
}

// total cash purchase sum
if (! function_exists('totalCashPurchase')) {
    function totalCashPurchase() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $this_year = date('Y');
        $this_month = date('m');
        $today = date('d');
        $from_date_this_month = $this_year.'-'.$this_month.'-01';
        $current_date_this_month = $this_year.'-'.$this_month.'-'.$today;

        $total_cash_purchase = 0;
        $total_cash_purchase_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(credit) as total_cash_purchase'))
            ->where('chart_of_account_transaction_details.chart_of_account_name','Cash In Hand')
            ->where('chart_of_account_transaction_details.transaction_type','Purchases')
            ->where('chart_of_account_transaction_details.transaction_date',date('Y-m-d'));

        $total_cash_purchase_history->whereBetween('chart_of_account_transaction_details.transaction_date',[$from_date_this_month, $current_date_this_month]);
        if($role !== 'Super Admin'){
            $total_cash_purchase_history->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }
        $total_cash_purchase_data = $total_cash_purchase_history->first();
        if(!empty($total_cash_purchase_data)){
            $total_cash_purchase = $total_cash_purchase_data->total_cash_purchase;
        }

        return $total_cash_purchase;
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
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $today_sale = 0;
        $today_sale_history = \App\ProductSale::select(DB::raw('SUM(grand_total_amount) as today_sale'))
            ->where('product_sales.sale_date',date('Y-m-d'));

        if($role !== 'Super Admin'){
            $today_sale_history->where('product_sales.store_id',$store_id);
        }
        $today_sale_data = $today_sale_history->first();
        if(!empty($today_sale_data)){
            $today_sale = $today_sale_data->today_sale;
        }





//        $today_sale_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(credit) as today_sale'))
//            ->where('chart_of_account_transaction_details.chart_of_account_name','Inventory')
//            ->where('chart_of_account_transaction_details.transaction_type','Sales')
//            ->where('chart_of_account_transaction_details.transaction_date',date('Y-m-d'));
//
//        if($role !== 'Super Admin'){
//            $today_sale_history->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
//            $today_sale_history->where('chart_of_account_transaction_details.store_id',$store_id);
//        }
//        $today_sale_data = $today_sale_history->first();
//        if(!empty($today_sale_data)){
//            $today_sale = $today_sale_data->today_sale;
//        }





        return $today_sale;
    }
}

// today cash sale sum
if (! function_exists('todayCashSale')) {
    function todayCashSale() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $today_cash_sale = 0;
        $today_cash_sale_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(debit) as today_cash_sale'))
            ->where('chart_of_account_transaction_details.chart_of_account_name','Cash In Hand')
            ->where('chart_of_account_transaction_details.transaction_type','Sales')
            ->where('chart_of_account_transaction_details.transaction_date',date('Y-m-d'));

        if($role !== 'Super Admin'){
            $today_cash_sale_history->where('chart_of_account_transaction_details.store_id',$store_id);
        }
        $today_cash_sale_data = $today_cash_sale_history->first();
        if(!empty($today_cash_sale_data)){
            $today_cash_sale = $today_cash_sale_data->today_cash_sale;
        }

        return $today_cash_sale;
    }
}

// total sale sum
if (! function_exists('totalSale')) {
    function totalSale() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $total_sale = 0;
        $total_sale_history = \App\ProductSale::select(DB::raw('SUM(grand_total_amount) as total_sale'));

        if($role !== 'Super Admin'){
            $total_sale_history->where('product_sales.store_id',$store_id);
        }
        $total_sale_data = $total_sale_history->first();
        if(!empty($total_sale_data)){
            $total_sale = $total_sale_data->total_sale;
        }


//        $total_sale_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(credit) as today_sale'))
//            ->where('chart_of_account_transaction_details.chart_of_account_name','Inventory')
//            ->where('chart_of_account_transaction_details.transaction_type','Sales');
//
//        if($role !== 'Super Admin'){
//            $total_sale_history->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
//            $total_sale_history->where('chart_of_account_transaction_details.store_id',$store_id);
//        }
//        $total_sale_data = $total_sale_history->first();
//        if(!empty($total_sale_data)){
//            $total_sale = $total_sale_data->today_sale;
//        }


        return $total_sale;
    }
}

// total cash sale sum
if (! function_exists('totalCashSale')) {
    function totalCashSale() {
        $currentUserDetails = currentUserDetails(Auth::user()->id);
        $role = $currentUserDetails['role'];
        $warehouse_id = $currentUserDetails['warehouse_id'];
        $store_id = $currentUserDetails['store_id'];

        $total_cash_sale = 0;
        $total_cash_sale_history = ChartOfAccountTransactionDetail::select(DB::raw('SUM(debit) as total_cash_sale'))
            ->where('chart_of_account_transaction_details.chart_of_account_name','Cash In Hand')
            ->where('chart_of_account_transaction_details.transaction_type','Sales')
            ->where('chart_of_account_transaction_details.transaction_date',date('Y-m-d'));

        if($role !== 'Super Admin'){
            $total_cash_sale_history->where('chart_of_account_transaction_details.store_id',$store_id);
        }
        $total_cash_sale_data = $total_cash_sale_history->first();
        if(!empty($total_cash_sale_data)){
            $total_cash_sale = $total_cash_sale_data->total_cash_sale;
        }

        return $total_cash_sale;
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

// warehouse info
if (! function_exists('warehouseInfo')) {
    function warehouseInfo($warehouse_id) {
        return DB::table('warehouses')
            ->where('id',$warehouse_id)
            ->first();
    }
}

// store name as id
if (! function_exists('storeName')) {
    function storeName($store_id) {
        return DB::table('stores')
            ->where('id',$store_id)
            ->pluck('name')
            ->first();
    }
}

// store info
if (! function_exists('storeInfo')) {
    function storeInfo($store_id) {
        return DB::table('stores')
            ->where('id',$store_id)
            ->first();
    }
}

// customer name as id
if (! function_exists('customerName')) {
    function customerName($customer_id) {
        return DB::table('customers')
            ->where('id',$customer_id)
            ->pluck('name')
            ->first();
    }
}

// customer info as id
if (! function_exists('customerInfo')) {
    function customerInfo($customer_id) {
        return DB::table('customers')
            ->where('id',$customer_id)
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

// supplier info as id
if (! function_exists('supplierInfo')) {
    function supplierInfo($supplier_id) {
        return DB::table('suppliers')
            ->where('id',$supplier_id)
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

// head_debit_or_credit
if (! function_exists('get_head_debit_or_credit')) {
    function get_head_debit_or_credit($name) {
        return DB::table('chart_of_accounts')
            ->where('head_name',$name)
            ->pluck('head_debit_or_credit')
            ->first();
    }
}

// warehouse and product current stock
if (! function_exists('warehouseProductCurrentStockByWarehouseAndProduct')) {
    function warehouseProductCurrentStockByWarehouseAndProduct($warehouse_id,$product_id) {
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
    function warehouseProductCurrentStockAmount() {
        $warehouse_current_stocks = DB::table('warehouse_current_stocks')
            ->join('products','warehouse_current_stocks.product_id','products.id')
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

// store and product current stock
if (! function_exists('storeProductCurrentStockByStoreAndProduct')) {
    function storeProductCurrentStockByStoreAndProduct($store_id,$product_id) {
        $store_current_stock = DB::table('warehouse_store_current_stocks')
            ->where('store_id',$store_id)
            ->where('product_id',$product_id)
            ->latest('id')
            ->pluck('current_stock')
            ->first();

        if($store_current_stock == NULL){
            $store_current_stock = 0;
        }
        return $store_current_stock;
    }
}

// store and product current stock amount
if (! function_exists('storeProductCurrentStockAmount')) {
    function storeProductCurrentStockAmount() {
        $store_current_stocks = DB::table('warehouse_store_current_stocks')
            ->join('products','warehouse_store_current_stocks.product_id','products.id')
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
                if(!empty($today_purchase_history)){
                    $today_purchase_amount = (int) $today_purchase_history->today_purchase;
                }else{
                    $today_purchase_amount = 0;
                }

                $today_cash_purchase_history = DB::table('product_purchases')
                    ->where('purchase_date', date('Y-m-d'))
                    ->where('payment_type_id', 1)
                    ->where('warehouse_id', $warehouse->id)
                    ->select(DB::raw('SUM(grand_total_amount) as today_cash_purchase'))
                    ->first();
                if(!empty($today_cash_purchase_history)){
                    $today_cash_purchase_amount = (int) $today_cash_purchase_history->today_cash_purchase;
                }else{
                    $today_cash_purchase_amount = 0;
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

                $total_cash_purchase_history = DB::table('product_purchases')
                    ->where('payment_type_id', 1)
                    ->where('warehouse_id', $warehouse->id)
                    ->select(DB::raw('SUM(grand_total_amount) as total_cash_purchase'))
                    ->first();
                if(!empty($total_cash_purchase_history)){
                    $total_cash_purchase_amount = (int) $total_cash_purchase_history->total_cash_purchase;
                }else{
                    $total_cash_purchase_amount = 0;
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
                $nested_data['warehouse_today_cash_purchase_amount']=$today_cash_purchase_amount;
                $nested_data['warehouse_total_cash_purchase_amount']=$total_cash_purchase_amount;
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
                $today_cash_sale = 0;
                $total_sale = 0;
                $total_cash_sale = 0;
                if(count($store_current_stocks) > 0){
                    foreach ($store_current_stocks as $store_current_stock){
                        $total_stock += $store_current_stock->current_stock;
                        $total_stock_amount += $store_current_stock->current_stock*$store_current_stock->purchase_price;
                    }
                }

                $today_sale_history = DB::table('product_sales')
                    ->where('sale_date', date('Y-m-d'))
                    ->where('store_id',$store->id)
                    ->select(DB::raw('SUM(grand_total_amount) as today_sale'),DB::raw('SUM(total_vat_amount) as today_sale_vat_amount'))
                    ->first();
                if(!empty($today_sale_history)){
                    $today_sale = $today_sale_history->today_sale - $today_sale_history->today_sale_vat_amount;
                }

                $today_cash_sale_history = DB::table('product_sales')
                    ->where('sale_date', date('Y-m-d'))
                    ->where('payment_type_id',1)
                    ->where('store_id',$store->id)
                    ->select(DB::raw('SUM(grand_total_amount) as today_cash_sale'),DB::raw('SUM(total_vat_amount) as today_cash_sale_vat_amount'))
                    ->first();
                if(!empty($today_cash_sale_history)){
                    $today_cash_sale = $today_cash_sale_history->today_cash_sale - $today_cash_sale_history->today_cash_sale_vat_amount;
                }

                $total_sale_history = DB::table('product_sales')
                    ->where('store_id',$store->id)
                    ->select(DB::raw('SUM(grand_total_amount) as total_sale'),DB::raw('SUM(total_vat_amount) as total_sale_vat_amount'))
                    ->first();
                if(!empty($total_sale_history)){
                    $total_sale = $total_sale_history->total_sale - $total_sale_history->total_sale_vat_amount;
                }

                $total_cash_sale_history = DB::table('product_sales')
                    ->where('payment_type_id',1)
                    ->where('store_id',$store->id)
                    ->select(DB::raw('SUM(grand_total_amount) as total_cash_sale'),DB::raw('SUM(total_vat_amount) as total_cash_sale_vat_amount'))
                    ->first();
                if(!empty($total_cash_sale_history)){
                    $total_cash_sale = $total_cash_sale_history->total_cash_sale - $total_cash_sale_history->total_cash_sale_vat_amount;
                }

                $staff = DB::table('users')
                    ->where('name','!=','production')
                    ->where('name','!=','Walk-In-Customer')
                    ->where('store_id',$store->id)
                    ->get()->count();

                $nested_data['store_name']=$store->name;
                $nested_data['store_staff']=$staff;
                $nested_data['store_today_sale_amount']=$today_sale;
                $nested_data['store_today_cash_sale_amount']=$today_cash_sale;
                $nested_data['store_total_sale_amount']=$total_sale;
                $nested_data['store_total_cash_sale_amount']=$total_cash_sale;
                $nested_data['store_current_stock']=$total_stock;
                $nested_data['store_current_stock_amount']=$total_stock_amount;

                array_push($store_arr,$nested_data);
            }
        }

        return $store_arr;
    }
}

// warehouse and product current stock
if (! function_exists('warehouseStoreProductCurrentStock')) {
    function warehouseStoreProductCurrentStock($store_id,$product_id) {
        $warehouse_store_current_stock = DB::table('warehouse_store_current_stocks')
            ->where('store_id',$store_id)
            ->where('product_id',$product_id)
            ->latest('id')
            ->pluck('current_stock')
            ->first();

        if($warehouse_store_current_stock == NULL){
            $warehouse_store_current_stock = 0;
        }
        return $warehouse_store_current_stock;
    }
}


if (! function_exists('checkExistsProduct')) {
    function checkExistsProduct($type,$product_category_id,$product_size_id=NULL,$product_unit_id,$product_sub_unit_id=NULL,$product_code=NULL) {

        if($type === 'Buy'){
            if( (!empty($product_sub_unit_id)) ){
                $check_exists_product = DB::table("products")
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_sub_unit_id',$product_sub_unit_id)
                    ->pluck('id')->first();
            }else{
                $check_exists_product = DB::table("products")
                    ->where('type', $type)
                    ->where('product_category_id', $product_category_id)
                    ->where('product_unit_id', $product_unit_id)
                    ->pluck('id')->first();
            }
        }else{
            if( (!empty($product_sub_unit_id)) && (!empty($product_code !== '')) ){
                $check_exists_product = DB::table("products")
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_size_id',$product_size_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_sub_unit_id',$product_sub_unit_id)
                    ->where('product_code',$product_code)
                    ->pluck('id')->first();
            }elseif( (!empty($product_sub_unit_id)) && (empty($product_code !== '')) ){
                $check_exists_product = DB::table("products")
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_size_id',$product_size_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_sub_unit_id',$product_sub_unit_id)
                    ->pluck('id')->first();
            }elseif( (empty($product_sub_unit_id)) && (!empty($product_code !== '')) ){
                $check_exists_product = DB::table("products")
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_size_id',$product_size_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_code',$product_code)
                    ->pluck('id')->first();
            }else{
                $check_exists_product = DB::table("products")
                    ->where('type', $type)
                    ->where('product_category_id', $product_category_id)
                    ->where('product_size_id', $product_size_id)
                    ->where('product_unit_id', $product_unit_id)
                    ->pluck('id')->first();
            }
        }

        return $check_exists_product;
    }
}

if (! function_exists('checkExistsProductForEdit')) {
    function checkExistsProductForEdit($product_id,$type,$product_category_id,$product_size_id=NULL,$product_unit_id,$product_sub_unit_id=NULL,$product_code=NULL) {

        if($type === 'Buy'){
            if( (!empty($product_sub_unit_id)) ){
                $check_exists_product = DB::table("products")
                    ->where('id','!=',$product_id)
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_sub_unit_id',$product_sub_unit_id)
                    ->pluck('id')->first();
            }else{
                $check_exists_product = DB::table("products")
                    ->where('id','!=',$product_id)
                    ->where('type', $type)
                    ->where('product_category_id', $product_category_id)
                    ->where('product_unit_id', $product_unit_id)
                    ->pluck('id')->first();
            }
        }else{
            if( (!empty($product_sub_unit_id)) && (!empty($product_code !== '')) ){
                $check_exists_product = DB::table("products")
                    ->where('id','!=',$product_id)
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_size_id',$product_size_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_sub_unit_id',$product_sub_unit_id)
                    ->where('product_code',$product_code)
                    ->pluck('id')->first();
            }elseif( (!empty($product_sub_unit_id)) && (empty($product_code !== '')) ){
                $check_exists_product = DB::table("products")
                    ->where('id','!=',$product_id)
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_size_id',$product_size_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_sub_unit_id',$product_sub_unit_id)
                    ->pluck('id')->first();
            }elseif( (empty($product_sub_unit_id)) && (!empty($product_code !== '')) ){
                $check_exists_product = DB::table("products")
                    ->where('id','!=',$product_id)
                    ->where('type',$type)
                    ->where('product_category_id',$product_category_id)
                    ->where('product_size_id',$product_size_id)
                    ->where('product_unit_id',$product_unit_id)
                    ->where('product_code',$product_code)
                    ->pluck('id')->first();
            }else{
                $check_exists_product = DB::table("products")
                    ->where('id','!=',$product_id)
                    ->where('type', $type)
                    ->where('product_category_id', $product_category_id)
                    ->where('product_size_id', $product_size_id)
                    ->where('product_unit_id', $product_unit_id)
                    ->pluck('id')->first();
            }
        }

        return $check_exists_product;
    }
}

// Create Product Name
if (! function_exists('createProductName')) {
    function createProductName($type,$product_category,$product_unit,$product_sub_unit,$product_size,$product_code) {
        if($type === 'Buy'){
            if(!empty($product_sub_unit)){
                $name = $product_category.'-'.$product_unit.'-'.$product_sub_unit;
            }else{
                $name = $product_category.'-'.$product_unit;
            }
        }else{
            if( (!empty($product_sub_unit)) && (!empty($product_code)) ){
                $name = $product_category.'-'.$product_unit.'-'.$product_sub_unit.'-'.$product_size.'-'.$product_code;
            }elseif( (empty($product_sub_unit)) && (!empty($product_code)) ){
                $name = $product_category.'-'.$product_unit.'-'.$product_size.'-'.$product_code;
            }elseif( (!empty($product_sub_unit)) && (empty($product_code)) ){
                $name = $product_category.'-'.$product_unit.'-'.$product_sub_unit.'-'.$product_size;
            }else{
                $name = $product_category.'-'.$product_unit.'-'.$product_size;
            }
        }
        return $name;
    }
}

if (! function_exists('productSearchForStockTransferByWarehouseId')) {
    function productSearchForStockTransferByWarehouseId($warehouse_id,$type,$product_category_id,$product_size_id=NULL,$product_unit_id,$product_sub_unit_id=NULL,$product_code=NULL) {

        if($type === 'Buy'){
            $product_infos = \App\Product::where('products.type',$type)
                ->where('product_category_id',$product_category_id)
                ->where('product_unit_id',$product_unit_id)
                ->select(
                    'id',
                    'type',
                    'product_category_id',
                    'product_unit_id',
                    'product_sub_unit_id',
                    'name',
                    'barcode',
                    'purchase_price',
                    'note',
                    'color',
                    'design',
                    'status',
                    'front_image',
                    'back_image'
                );

            if(!empty($product_sub_unit_id)){
                $product_infos->where('product_sub_unit_id',$product_sub_unit_id);
            }

            $product_infos_data = $product_infos->latest('id')->get();

            $product_data = [];
            foreach($product_infos_data as $product_info){
                $current_stock = \App\WarehouseCurrentStock::where('warehouse_id',$warehouse_id)
                    ->where('product_id',$product_info['id'])
                    ->pluck('current_stock')
                    ->first();

                $nested_data['id']=$product_info['id'];
                $nested_data['type']=$product_info['type'];
                $nested_data['category_id']=$product_info['product_category_id'];
                $nested_data['category_name']=$product_info->category->name;
                $nested_data['unit_id']=$product_info['product_unit_id'];
                $nested_data['unit_name']=$product_info->unit->name;
                $nested_data['sub_unit_id']=$product_info['product_sub_unit_id'];
                $nested_data['sub_unit_name']=$product_info['product_sub_unit_id'] ? $product_info->sub_unit->name : '';
                $nested_data['name']=$product_info['name'];
                $nested_data['barcode']=$product_info['barcode'];
                $nested_data['purchase_price']=$product_info['purchase_price'];
                $nested_data['note']=$product_info['note'];
                $nested_data['color']=$product_info['color'];
                $nested_data['design']=$product_info['design'];
                $nested_data['status']=$product_info['status'];
                $nested_data['front_image']=$product_info['front_image'];
                $nested_data['back_image']=$product_info['back_image'];
                $nested_data['qty']= 0;
                $nested_data['current_stock']=!empty($current_stock) ? $current_stock : 0;

                array_push($product_data, $nested_data);
            }
        }else{
            $product_infos = \App\Product::where('products.type', $type)
                ->where('product_category_id', $product_category_id)
                ->where('product_size_id', $product_size_id)
                ->where('product_unit_id', $product_unit_id)
                ->select(
                    'id',
                    'type',
                    'product_category_id',
                    'product_unit_id',
                    'product_sub_unit_id',
                    'name',
                    'barcode',
                    'purchase_price',
                    'note',
                    'color',
                    'design',
                    'status',
                    'front_image',
                    'back_image'
                );

            if (!empty($product_sub_unit_id)) {
                $product_infos->where('product_sub_unit_id', $product_sub_unit_id);
            }
            if (!empty($product_code)) {
                $product_infos->where('product_code', $product_code);
            }

            $product_infos_data = $product_infos->latest('id')->get();


            $product_data = [];
            foreach($product_infos_data as $product_info){
                $current_stock = \App\WarehouseCurrentStock::where('warehouse_id',$warehouse_id)
                    ->where('product_id',$product_info['id'])
                    ->pluck('current_stock')
                    ->first();

                $nested_data['id']=$product_info['id'];
                $nested_data['type']=$product_info['type'];
                $nested_data['category_id']=$product_info['product_category_id'];
                $nested_data['category_name']=$product_info->category->name;
                $nested_data['size_id']=$product_info['product_size_id'];
                $nested_data['size_name']=$product_info->size->name;
                $nested_data['unit_id']=$product_info['product_unit_id'];
                $nested_data['unit_name']=$product_info->unit->name;
                $nested_data['sub_unit_id']=$product_info['product_sub_unit_id'];
                $nested_data['sub_unit_name']=$product_info['product_sub_unit_id'] ? $product_info->sub_unit->name : '';
                $nested_data['product_code']=$product_info['product_code'];
                $nested_data['name']=$product_info['name'];
                $nested_data['barcode']=$product_info['barcode'];
                $nested_data['purchase_price']=$product_info['purchase_price'];
                $nested_data['note']=$product_info['note'];
                $nested_data['color']=$product_info['color'];
                $nested_data['design']=$product_info['design'];
                $nested_data['status']=$product_info['status'];
                $nested_data['front_image']=$product_info['front_image'];
                $nested_data['back_image']=$product_info['back_image'];
                $nested_data['qty']= 0;
                $nested_data['current_stock']=!empty($current_stock) ? $current_stock : 0;

                array_push($product_data, $nested_data);
            }
        }

        return $product_data;
    }
}

if (! function_exists('productSearchForSaleByStoreId')) {
    function productSearchForSaleByStoreId($store_id,$type,$product_category_id,$product_size_id,$product_unit_id,$product_sub_unit_id=NULL,$product_code=NULL) {

        if($type === 'Buy'){
            $product_infos = \App\Product::where('products.type',$type)
                ->where('product_category_id',$product_category_id)
                ->where('product_unit_id',$product_unit_id)
                ->where('product_code',$product_code)
                ->select(
                    'id',
                    'type',
                    'product_category_id',
                    'product_unit_id',
                    'product_code',
                    'name',
                    'barcode',
                    'purchase_price',
                    'note',
                    'color',
                    'design',
                    'status',
                    'front_image',
                    'back_image'
                );
            if(!empty($product_sub_unit_id)){
                $product_infos->where('product_sub_unit_id',$product_sub_unit_id);
            }

            $product_infos_data = $product_infos->latest('id')->get();

            $product_data = [];
            if(count($product_infos_data) > 0) {
                foreach ($product_infos_data as $product_info) {
                    $current_stock = \App\WarehouseStoreCurrentStock::where('store_id', $store_id)
                        ->where('product_id', $product_info['id'])
                        ->pluck('current_stock')
                        ->first();

                    $nested_data['id'] = $product_info['id'];
                    $nested_data['type'] = $product_info['type'];
                    $nested_data['category_id'] = $product_info['product_category_id'];
                    $nested_data['category_name'] = $product_info->category->name;
                    $nested_data['unit_id'] = $product_info['product_unit_id'];
                    $nested_data['unit_name'] = $product_info->unit->name;
                    $nested_data['product_code'] = $product_info['product_code'];
                    $nested_data['name'] = $product_info['name'];
                    $nested_data['barcode'] = $product_info['barcode'];
                    $nested_data['purchase_price'] = $product_info['purchase_price'];
                    $nested_data['note'] = $product_info['note'];
                    $nested_data['color'] = $product_info['color'];
                    $nested_data['design'] = $product_info['design'];
                    $nested_data['status'] = $product_info['status'];
                    $nested_data['front_image'] = $product_info['front_image'];
                    $nested_data['back_image'] = $product_info['back_image'];
                    $nested_data['qty'] = 0;
                    $nested_data['current_stock'] = !empty($current_stock) ? $current_stock : 0;

                    array_push($product_data, $nested_data);
                }
            }
        }else{

            $product_infos = \App\Product::where('products.type',$type)
                ->where('product_category_id',$product_category_id)
                ->where('product_size_id',$product_size_id)
                ->where('product_unit_id',$product_unit_id)
                ->select(
                    'id',
                    'type',
                    'product_category_id',
                    'product_unit_id',
                    'product_code',
                    'name',
                    'barcode',
                    'purchase_price',
                    'note',
                    'color',
                    'design',
                    'status',
                    'front_image',
                    'back_image'
                );

            if(!empty($product_sub_unit_id)){
                $product_infos->where('product_sub_unit_id',$product_sub_unit_id);
            }

            if(!empty($product_code)){
                $product_infos->where('product_code',$product_code);
            }

            $product_infos_data = $product_infos->latest('id')->get();

            $product_data = [];
            if(count($product_infos_data) > 0){
                foreach($product_infos_data as $product_info){

                    $current_stock = \App\WarehouseStoreCurrentStock::where('store_id',$store_id)
                        ->where('product_id',$product_info['id'])
                        ->pluck('current_stock')
                        ->first();

                    $product = Product::find($product_info['id']);

                    $nested_data['id']=$product_info['id'];
                    $nested_data['type']=$product_info['type'];
                    $nested_data['category_id']=$product_info['product_category_id'];
                    $nested_data['category_name']=$product_info->category->name;
                    $nested_data['size_id']=$product_info['product_size_id'];
                    $nested_data['size_name']=$product->size->name;
                    $nested_data['unit_id']=$product_info['product_unit_id'];
                    $nested_data['unit_name']=$product->unit->name;
                    $nested_data['sub_unit_id']=$product_info['product_sub_unit_id'];
                    $nested_data['sub_unit_name']=$product_info['product_sub_unit_id'] ? $product->sub_unit->name : '';
                    $nested_data['product_code']=$product_info['product_code'];
                    $nested_data['name']=$product_info['name'];
                    $nested_data['barcode']=$product_info['barcode'];
                    $nested_data['purchase_price']=$product_info['purchase_price'];
                    $nested_data['note']=$product_info['note'];
                    $nested_data['color']=$product_info['color'];
                    $nested_data['design']=$product_info['design'];
                    $nested_data['status']=$product_info['status'];
                    $nested_data['front_image']=$product_info['front_image'];
                    $nested_data['back_image']=$product_info['back_image'];
                    $nested_data['qty']= 0;
                    $nested_data['current_stock']=!empty($current_stock) ? $current_stock : 0;

                    array_push($product_data, $nested_data);
                }
            }
        }
        return $product_data;
    }
}



//Trial Balance Report
if (! function_exists('trial_balance_report')) {
//    function trial_balance_report($FromDate, $ToDate, $WithOpening) {
    function trial_balance_report($FromDate, $ToDate, $warehouse_id, $store_id, $WithOpening) {

        if ($WithOpening)
            $WithOpening = true;
        else
            $WithOpening = false;

        $data = array(
            'oResultTr' => '',
            'oResultInEx' => '',
            'WithOpening' => $WithOpening
        );

        $oResultTr = \App\ChartOfAccount::where('is_active',1)
            ->where('is_general_ledger',1)
            ->whereIn('head_type',['A','L'])
            ->orderBy('head_code')
            ->get();

        $TotalCredit=0;
        $TotalDebit=0;
        $oResultTrialData = [];
        for($i=0;$i<count($oResultTr);$i++) {
            $head_code = $oResultTr[$i]['head_code'];
            $head_name = $oResultTr[$i]['head_name'];

            if( ($warehouse_id !== null) && ($store_id !== null) ){
                $oResultTrial = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like','%'.$head_code.'%')
                    ->where('warehouse_id',$warehouse_id)
                    ->where('store_id',$store_id)
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }elseif( ($warehouse_id !== null) ){
                $oResultTrial = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like','%'.$head_code.'%')
                    ->where('warehouse_id',$warehouse_id)
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }elseif( ($store_id !== null) ){
                $oResultTrial = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like','%'.$head_code.'%')
                    ->where('store_id',$store_id)
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }else{
                $oResultTrial = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like','%'.$head_code.'%')
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }


            if($oResultTrial->Credit+$oResultTrial->Debit > 0)
            {
                if($oResultTrial->Debit>$oResultTrial->Credit)
                {
                    $TotalDebit += $oResultTrial->Debit-$oResultTrial->Credit;
                    $debit = number_format($oResultTrial->Debit-$oResultTrial->Credit, 2);
                    $credit = number_format(0,2);
                }else{
                    $TotalCredit += $oResultTrial->Credit-$oResultTrial->Debit;
                    $debit = number_format(0,2);
                    $credit = number_format($oResultTrial->Credit-$oResultTrial->Debit,2);
                }

                $nested_data['head_code']=$head_code;
                $nested_data['head_name']=$head_name;
                $nested_data['debit']=$debit;
                $nested_data['credit']=$credit;

                array_push($oResultTrialData, $nested_data);
            }
        }

        $oResultInEx = \App\ChartOfAccount::where('is_active',1)
            ->where('is_general_ledger',1)
            ->whereIn('head_type',['I','E'])
            ->orderBy('head_code')
            ->get();

        for($i=0;$i<count($oResultInEx);$i++) {
            $head_code = $oResultInEx[$i]['head_code'];
            $head_name = $oResultInEx[$i]['head_name'];

            //$sql = "SELECT SUM(acc_transaction.Debit) AS Debit, SUM(acc_transaction.Credit) AS Credit FROM acc_transaction WHERE acc_transaction.IsAppove =1 AND VDate BETWEEN '" . $dtpFromDate . "' AND '" . $dtpToDate . "' AND COAID LIKE '$COAID%' ";
            if( ($warehouse_id !== null) && ($store_id !== null) ){
                $oResultIE = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like',$head_code.'%')
                    ->where('warehouse_id',$warehouse_id)
                    ->where('store_id',$store_id)
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }elseif( ($warehouse_id !== null) ){
                $oResultIE = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like',$head_code.'%')
                    ->where('warehouse_id',$warehouse_id)
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }elseif( ($store_id !== null) ){
                $oResultIE = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like',$head_code.'%')
                    ->where('store_id',$store_id)
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }else{
                $oResultIE = \App\ChartOfAccountTransactionDetail::where('chart_of_account_number','like',$head_code.'%')
                    ->whereBetween('transaction_date',[$FromDate,$ToDate])
                    ->select(DB::raw('SUM(debit) as Debit'),DB::raw('SUM(credit) as Credit'))
                    ->first();
            }

            if($oResultIE->Credit+$oResultIE->Debit > 0)
            {
                if($oResultIE->Debit > $oResultIE->Credit)
                {
                    $TotalDebit += $oResultIE->Debit-$oResultIE->Credit;
                    $debit = number_format($oResultIE->Debit-$oResultIE->Credit,2);
                    $credit = number_format(0,2);
                }else{
                    $TotalCredit += $oResultIE->Credit-$oResultIE->Debit;
                    $debit = number_format(0,2);
                    $credit = number_format($oResultIE->Credit-$oResultIE->Debit,2);
                }

                $nested_data['head_code']=$head_code;
                $nested_data['head_name']=$head_name;
                $nested_data['debit']=$debit;
                $nested_data['credit']=$credit;

                array_push($oResultTrialData, $nested_data);
            }
        }

        $ProfitLoss=$TotalDebit-$TotalCredit;
        if($ProfitLoss!=0)
        {
            if($ProfitLoss<0)
            {
                $TotalDebit += abs($ProfitLoss);
                $d = number_format( abs($ProfitLoss),2);
                $c = number_format(0,2);
            }else if($ProfitLoss>0)
            {
                $TotalCredit+= abs($ProfitLoss);
                $c = number_format(abs($ProfitLoss),2);
                $d = number_format(0,2);
            }else{
                $c = number_format(0,2);
                $d = number_format(0,2);
            }

            $nested_data['head_code']='';
            $nested_data['head_name']='Balance C/D';
            $nested_data['debit']= $d;
            $nested_data['credit']=$c;

            array_push($oResultTrialData, $nested_data);
        }

        $final_data = [
            'ResultTr' => $oResultTrialData,
            'TotalDebit' => number_format($TotalDebit,2),
            'TotalCredit' => number_format($TotalCredit,2)
        ];

        return $final_data;
    }
}

if (! function_exists('supplierCurrentTotalDueByCustomerId')) {
    function supplierCurrentTotalDueByCustomerId($supplier_id) {
        $current_total_due = 0;
        $supplier_current_total_due = DB::table('suppliers')
            ->where('id',$supplier_id)
            ->select('id','current_total_due')
            ->first();

        if(!empty($supplier_current_total_due)){
            $current_total_due = $supplier_current_total_due->current_total_due;
        }
        return $current_total_due;
    }
}

if (! function_exists('customerCurrentTotalDueByCustomerId')) {
    function customerCurrentTotalDueByCustomerId($customer_id) {
        $current_total_due = 0;
        $customer_current_total_due = DB::table('customers')
            ->where('id',$customer_id)
            ->select('id','current_total_due')
            ->first();

        if(!empty($customer_current_total_due)){
            $current_total_due = $customer_current_total_due->current_total_due;
        }
        return $current_total_due;
    }
}

if(!function_exists('stock')){
    function stock(
        $ref_id=NULL,
        $user_id,
        $warehouse_id=NULL,
        $store_id=NULL,
        $product_info,
        $stock_type,
        $stock_where,
        $stock_in_out,
        $previous_stock,
        $stock_in_qty,
        $stock_out_qty,
        $current_stock_qty,
        $stock_date,
        $stock_date_time
    ){
        $stock = new Stock();
        $stock->ref_id = $ref_id;
        $stock->user_id = $user_id;
        $stock->warehouse_id = $warehouse_id;
        $stock->store_id = $store_id;
        $stock->product_id = $product_info->id;
        $stock->product_name = $product_info->name;
        $stock->product_type = $product_info->type;
        $stock->stock_type = $stock_type;
        $stock->stock_where = $stock_where;
        $stock->stock_in_out = $stock_in_out;
        $stock->previous_stock = $previous_stock;
        $stock->stock_in = $stock_in_qty;
        $stock->stock_out = $stock_out_qty;
        $stock->current_stock = $current_stock_qty;
        $stock->stock_date = $stock_date;
        $stock->stock_date_time = $stock_date_time;
        $stock->save();
        if($stock->id){
            return true;
        }else{
            return false;
        }
    }
}

if (! function_exists('chartOfAccountTransactionDetails')) {
    function chartOfAccountTransactionDetails(
        $ref_id=NULL,
        $invoice_no=NULL,
        $user_id,
        $voucher_type_id,
        $voucher_no,
        $transaction_type,
        $transaction_date,
        $transaction_date_time,
        $year,
        $month,
        $warehouse_id=NULL,
        $store_id=NULL,
        $payment_type_id,
        $cheque_date,
        $cheque_approved_status,
        $chart_of_account_transaction_id=NULL,
        $chart_of_account_id,
        $chart_of_account_number,
        $chart_of_account_name,
        $chart_of_account_parent_name,
        $chart_of_account_type,
        $debit,
        $credit,
        $description,
        $approved_status
    ) {
        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
        $chart_of_account_transaction_details->ref_id = $ref_id;
        $chart_of_account_transaction_details->invoice_no = $invoice_no;
        $chart_of_account_transaction_details->user_id = $user_id;
        $chart_of_account_transaction_details->voucher_type_id = $voucher_type_id;
        $chart_of_account_transaction_details->voucher_no = $voucher_no;
        $chart_of_account_transaction_details->transaction_type = $transaction_type;
        $chart_of_account_transaction_details->transaction_date = $transaction_date;
        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
        $chart_of_account_transaction_details->year = $year;
        $chart_of_account_transaction_details->month = $month;
        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
        $chart_of_account_transaction_details->store_id = $store_id;
        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
        $chart_of_account_transaction_details->cheque_date = $cheque_date;
        $chart_of_account_transaction_details->cheque_approved_status = $cheque_approved_status;
        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transaction_id;
        $chart_of_account_transaction_details->chart_of_account_id = $chart_of_account_id;
        $chart_of_account_transaction_details->chart_of_account_number = $chart_of_account_number;
        $chart_of_account_transaction_details->chart_of_account_name = $chart_of_account_name;
        $chart_of_account_transaction_details->chart_of_account_parent_name = $chart_of_account_parent_name;
        $chart_of_account_transaction_details->chart_of_account_type = $chart_of_account_type;
        $chart_of_account_transaction_details->debit = $debit;
        $chart_of_account_transaction_details->credit = $credit;
        $chart_of_account_transaction_details->description = $description;
        $chart_of_account_transaction_details->approved_status = $approved_status;
        if ($chart_of_account_transaction_details->save()) {
            return true;
        }else{
            return false;
        }
    }
}


