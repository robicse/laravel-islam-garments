<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Customer;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductSaleReturnCollection;
use App\Party;
use App\PaymentCollection;
use App\PaymentType;
use App\Product;
use App\ProductSale;
use App\ProductSaleDetail;
use App\ProductSaleReturn;
use App\ProductSaleReturnDetail;
use App\Stock;
use App\Store;
use App\Transaction;
use App\VoucherType;
use App\WarehouseCurrentStock;
use App\WarehouseStoreCurrentStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ProductSaleReturnController extends Controller
{
// product sale invoice list pagination
    public function productSaleInvoiceListPaginationWithSearch(Request $request){
        if($request->search){
            $product_sale_invoices = DB::table('product_sales')
                ->where('invoice_no','like','%'.$request->search.'%')
                ->select('id','invoice_no','grand_total_amount')
                ->paginate(12);
        }else{
            $product_sale_invoices = DB::table('product_sales')
                ->select('id','invoice_no','grand_total_amount')
                ->paginate(12);
        }

        if($product_sale_invoices === null){
            $response = APIHelpers::createAPIResponse(true,404,'No Product Sale List Found.',null);
            return response()->json($response,404);
        }else{
            $response = APIHelpers::createAPIResponse(false,200,'',$product_sale_invoices);
            return response()->json($response,200);
        }
    }

    public function productSaleDetails(Request $request){
        //dd($request->all());
        $this->validate($request, [
            'product_sale_invoice_no'=> 'required',
        ]);

        $product_sales = DB::table('product_sales')
            ->leftJoin('users','product_sales.user_id','users.id')
            ->leftJoin('stores','product_sales.store_id','stores.id')
            ->leftJoin('customers','product_sales.customer_id','customers.id')
            ->where('product_sales.invoice_no',$request->product_sale_invoice_no)
            ->select(
                'product_sales.id as product_sale_id',
                'product_sales.invoice_no',
                'product_sales.discount_type',
                'product_sales.discount_amount',
                'product_sales.grand_total_amount',
                'product_sales.paid_amount',
                'product_sales.due_amount',
                'product_sales.sale_date_time',
                'users.name as user_name',
                'customers.id as customer_id',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'customers.address as customer_address',
                'stores.id as store_id',
                'stores.name as store_name',
                'stores.phone as store_phone',
                'stores.address as store_address'
            )
            ->first();

        if($product_sales){

            $product_sale_details = DB::table('product_sales')
                ->join('product_sale_details','product_sales.id','product_sale_details.product_sale_id')
                ->leftJoin('products','product_sale_details.product_id','products.id')
                ->where('product_sales.invoice_no',$request->product_sale_invoice_no)
                ->select(
                    'product_sale_details.id as product_sale_detail_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'product_sale_details.qty',
                    'product_sale_details.qty as current_qty',
                    'product_sale_details.purchase_price',
                    'product_sale_details.sale_date',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id'
                )
                ->get();

            $product_sale_arr = [];
            if(count($product_sale_details) > 0){
                foreach ($product_sale_details as $product_sale_detail){
                    $already_return_qty = DB::table('product_sale_return_details')
                        ->where('product_sale_detail_id',$product_sale_detail->product_sale_detail_id)
                        ->where('product_id',$product_sale_detail->product_id)
                        ->pluck('qty')
                        ->first();

                    $product = Product::find($product_sale_detail->product_id);

                    $nested_data['product_sale_detail_id'] = $product_sale_detail->product_sale_detail_id;
                    $nested_data['product_id'] = $product_sale_detail->product_id;
                    $nested_data['product_name'] = $product_sale_detail->product_name;
                    $nested_data['product_category_id'] = $product_sale_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $product_sale_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$product_sale_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$product_sale_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $product_sale_detail->product_size_id;
                    $nested_data['product_size_name'] = $product->size->name;
                    $nested_data['qty'] = $product_sale_detail->qty;
                    $nested_data['sale_qty'] = $product_sale_detail->qty;
                    $nested_data['current_qty'] = $product_sale_detail->current_qty;
                    $nested_data['already_return_qty'] = $already_return_qty;
                    $nested_data['exists_return_qty'] = $product_sale_detail->qty - $already_return_qty;
                    $nested_data['purchase_price'] = $product_sale_detail->purchase_price;


                    array_push($product_sale_arr,$nested_data);

                }
            }

            $success['product_sales'] = $product_sales;
            $success['product_sale_details'] = $product_sale_arr;
            $response = APIHelpers::createAPIResponse(false,200,'',$success);
            return response()->json($response,200);
        }else{
            $response = APIHelpers::createAPIResponse(true,404,'No Product Sale List Found.',null);
            return response()->json($response,404);
        }
    }

    public function productSaleReturnCreate(Request $request){
        //dd($request->all());
        $this->validate($request, [
            //'product_sale_invoice_no'=> 'required',
            'store_id'=> 'required',
            'customer_id'=> 'required',
            //'payment_type_id'=> 'required',
            //'paid_amount'=> 'required',
            //'due_amount'=> 'required',
            //'sub_total_amount'=> 'required',
            //'grand_total_amount'=> 'required',
        ]);

        $get_invoice_no = ProductSaleReturn::latest('id','desc')->pluck('invoice_no')->first();
        if(!empty($get_invoice_no)){
            $get_invoice = str_replace("sale-return","",$get_invoice_no);
            $invoice_no = $get_invoice+1;
        }else{
            $invoice_no = 800800;
        }
        $final_invoice = 'sale-return'.$invoice_no;

        $date = date('Y-m-d');
        $date_time = date('Y-m-d h:i:s');

        $user_id = Auth::user()->id;
        $store_id = $request->store_id;
        $warehouse_id = NULL;
        $product_sale_invoice_no = $request->product_sale_invoice_no ? $request->product_sale_invoice_no : NULL;
        $customer_id = $request->customer_id;
        $payment_type_id = $request->payment_type_id ? $request->payment_type_id : NULL;
        $sub_total_amount = $request->sub_total_amount;
        $discount_type = $request->discount_type ? $request->discount_type : NULL;
        $discount_amount = $request->discount_amount ? $request->discount_amount : 0;
        $grand_total_amount = $request->grand_total_amount;
        //$sale_invoice_no = $request->sale_invoice_no;
        $products = json_decode($request->products);

        $product_sale_info = ProductSale::where('invoice_no',$product_sale_invoice_no)->first();
        //$product_sale_id = $product_sale_info->id;

        // product sale return
        $productSaleReturn = new ProductSaleReturn();
        $productSaleReturn ->invoice_no = $final_invoice;
        $productSaleReturn ->product_sale_invoice_no = $product_sale_invoice_no;
        $productSaleReturn ->user_id = $user_id;
        $productSaleReturn ->customer_id = $customer_id;
        $productSaleReturn ->store_id = $store_id;
        $productSaleReturn ->sub_total_amount = $sub_total_amount;
        $productSaleReturn ->product_sale_return_type = 'sale_return';
        $productSaleReturn ->discount_type = $discount_type;
        $productSaleReturn ->discount_amount = $discount_amount;
        $productSaleReturn ->grand_total_amount = $grand_total_amount;
        $productSaleReturn ->paid_amount = $grand_total_amount;
        $productSaleReturn ->due_amount = 0;
        $productSaleReturn ->product_sale_return_date = $date;
        $productSaleReturn->save();
        $insert_id = $productSaleReturn->id;

        if($insert_id)
        {
            // for live testing
            foreach ($products as $data) {

                $product_id =  $data->id;
                $qty =  $data->qty;
                $price =  $data->purchase_price;
                //$product_sale_detail_id =  $data->product_sale_detail_id ? $data->product_sale_detail_id : NULL;

                $get_purchase_price = Product::where('id',$product_id)->pluck('purchase_price')->first();

                // product purchase detail
                $purchase_sale_return_detail = new ProductSaleReturnDetail();
                $purchase_sale_return_detail->product_sale_return_id = $insert_id;
                $purchase_sale_return_detail->product_sale_detail_id = NULL;
                $purchase_sale_return_detail->product_id = $product_id;
                $purchase_sale_return_detail->purchase_price = $get_purchase_price;
                $purchase_sale_return_detail->qty = $qty;
                $purchase_sale_return_detail->price = $price;
                $purchase_sale_return_detail->sub_total = $qty*$price;
                $purchase_sale_return_detail->save();

//                $sale_type = ProductSale::where('invoice_no',$sale_invoice_no)->pluck('sale_type')->first();
//
//                if($sale_type === 'whole_sale') {
//                    $check_previous_stock = Stock::where('store_id', $store_id)->where('stock_where', 'store')->where('product_id', $product_id)->latest()->pluck('current_stock')->first();
//                }

                $check_previous_stock = Stock::where('store_id', $store_id)->where('stock_where', 'store')->where('product_id', $product_id)->latest()->pluck('current_stock')->first();

                if(!empty($check_previous_stock)){
                    $previous_stock = $check_previous_stock;
                }else{
                    $previous_stock = 0;
                }

                // product stock
                $stock = new Stock();
                $stock->ref_id = $insert_id;
                $stock->user_id = $user_id;
                $stock->warehouse_id = $warehouse_id;
                $stock->store_id = $store_id;
                $stock->product_id = $product_id;
                $stock->stock_type = 'sale_return';
                $stock->stock_where = 'store';
                $stock->stock_in_out = 'stock_in';
                $stock->previous_stock = $previous_stock;
                $stock->stock_in = $qty;
                $stock->stock_out = 0;
                $stock->current_stock = $previous_stock + $qty;
                $stock->stock_date = $date;
                $stock->stock_date_time = $date_time;
                $stock->save();

                // warehouse current stock
                $update_store_current_stock = WarehouseStoreCurrentStock::where('store_id',$store_id)
                    ->where('product_id',$product_id)
                    ->first();

                $exists_current_stock = $update_store_current_stock->current_stock;
                $final_store_current_stock = $exists_current_stock + $qty;
                $update_store_current_stock->current_stock=$final_store_current_stock;
                $update_store_current_stock->save();
            }

            // customer due update
            $customer = Customer::find($customer_id);
            $previous_current_total_due = $customer->current_total_due;
            $update_current_total_due = $previous_current_total_due - $grand_total_amount;

            $customer->current_total_due = $update_current_total_due;
            $customer->save();


            // 2nd theme

            // Cash In Hand Account Info
//            $cash_chart_of_account_info = ChartOfAccount::where('head_name','Cash In Hand')->first();
//
//            // Cheque Account Info
//            $cheque_chart_of_account_info = ChartOfAccount::where('head_name','Cheque')->first();
//
//            // Account Receivable Account Info
//            $account_receivable_info = ChartOfAccount::where('head_name','Account Receivable')->first();
//
//            // Mobile Banking Account Info
//            $mobile_banking_chart_of_account_info = ChartOfAccount::where('head_name','Mobile Banking')->first();
//
//            // Inventory Account Info
//            $inventory_chart_of_account_info = ChartOfAccount::where('head_name', 'Inventory')->first();
//
//            // Product Sale Info
//            $product_sale_chart_of_account_info = ChartOfAccount::where('head_name', 'Product Sale')->first();
//
//            // customer head
//            $code = Customer::where('id',$customer_id)->pluck('code')->first();
//            $customer_chart_of_account_info = ChartOfAccount::where('name_code',$code)->first();
//
//            // voucher no
//            $get_voucher_name = VoucherType::where('id',7)->pluck('name')->first();
//            $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',7)->latest()->pluck('voucher_no')->first();
//            if(!empty($get_voucher_no)){
//                $get_voucher_name_str = $get_voucher_name."-";
//                $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
//                $voucher_no = $get_voucher+1;
//            }else{
//                $voucher_no = 7000;
//            }
//            $final_voucher_no = $get_voucher_name.'-'.$voucher_no;
//
//            //Inventory Debit
//            $description = $inventory_chart_of_account_info->head_name.' Store Inventory Debited For Sales';
//            chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $inventory_chart_of_account_info->id, $inventory_chart_of_account_info->head_code, $inventory_chart_of_account_info->head_name, $inventory_chart_of_account_info->parent_head_name, $inventory_chart_of_account_info->head_type, $grand_total_amount, NULL, $description, 'Approved');
//
//            // Customer Credit
//            $description = $customer_chart_of_account_info->head_name.' Customer Credited For Paid Amount Sales';
//            chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, NULL, $grand_total_amount, $description, 'Approved');
//
//            // Customer Debit
//            $description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
//            chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, NULL, $grand_total_amount, $description, 'Approved');
//
//            if($payment_type_id === '1'){
//                // Cash In Hand Debit
//                $description = $cash_chart_of_account_info->head_name. 'Store Cash In Hand Debited For Paid Amount sales';
//                chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, $grand_total_amount, NULL, $description, 'Approved');
//            }
//
//            if($payment_type_id === '2') {
//                // Cheque Debit
//                $description = $cheque_chart_of_account_info->head_name. ' Store Cheque Debited For Paid Amount Sales';
//                chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $cheque_chart_of_account_info->id, $cheque_chart_of_account_info->head_code, $cheque_chart_of_account_info->head_name, $cheque_chart_of_account_info->parent_head_name, $cheque_chart_of_account_info->head_type, $grand_total_amount, NULL, $description, 'Pending');
//            }
//
//            if($payment_type_id === '3') {
//                // Mobile Banking
//                $description = $mobile_banking_chart_of_account_info->head_name. ' For Paid Amount Sales';
//                chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $mobile_banking_chart_of_account_info->id, $mobile_banking_chart_of_account_info->head_code, $mobile_banking_chart_of_account_info->head_name, $cheque_chart_of_account_info->parent_head_name, $mobile_banking_chart_of_account_info->head_type, $grand_total_amount, NULL, $description, 'Approved');
//            }






            $response = APIHelpers::createAPIResponse(false,201,'Product Sale Return Added Successfully.',null);
            return response()->json($response,201);
        }else{
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }


    public function productSaleReturnListWithSearch(Request $request){
        $product_whole_sales = DB::table('product_sale_returns')
            ->leftJoin('users','product_sale_returns.user_id','users.id')
            ->leftJoin('stores','product_sale_returns.store_id','stores.id')
            ->where('product_sale_returns.invoice_no','like','%'.$request->search.'%')
            ->orWhere('stores.name','like','%'.$request->search.'%')
            ->select(
                'product_sale_returns.id',
                'product_sale_returns.invoice_no',
                'product_sale_returns.product_sale_invoice_no',
                'product_sale_returns.discount_type',
                'product_sale_returns.discount_amount',
                //'product_sale_returns.total_vat_amount',
                'product_sale_returns.grand_total_amount',
                'product_sale_returns.paid_amount',
                'product_sale_returns.due_amount',
                'product_sale_returns.payment_type_id',
                'users.name as user_name',
                'stores.id as store_id',
                'stores.name as store_name',
                'stores.address as store_address'
            )
            ->orderBy('product_sale_returns.id','desc')
            ->get();

        if(count($product_whole_sales) > 0)
        {
            $product_whole_sale_arr = [];
            foreach ($product_whole_sales as $data){
                $payment_type = PaymentType::where('id',$data->payment_type_id)->pluck('name')->first();

                $nested_data['id']=$data->id;
                $nested_data['invoice_no']=ucfirst($data->invoice_no);
                $nested_data['product_sale_invoice_no']=$data->product_sale_invoice_no;
                $nested_data['discount_type']=$data->discount_type;
                $nested_data['discount_amount']=$data->discount_amount;
                $nested_data['grand_total_amount']=$data->grand_total_amount;
                $nested_data['paid_amount']=$data->paid_amount;
                $nested_data['due_amount']=$data->due_amount;
                $nested_data['user_name']=$data->user_name;
                $nested_data['store_id']=$data->store_id;
                $nested_data['store_name']=$data->store_name;
                $nested_data['store_address']=$data->store_address;
                $nested_data['payment_type']=$payment_type;

                array_push($product_whole_sale_arr,$nested_data);
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$product_whole_sale_arr);
            return response()->json($response,200);
        }else{
            $response = APIHelpers::createAPIResponse(true,404,'No Sale Return List Found.',null);
            return response()->json($response,404);
        }
    }


    public function productSaleReturnListPaginationWithSearch(Request $request){
        $user_id = Auth::user()->id;
        $currentUserDetails = currentUserDetails($user_id);
        $role = $currentUserDetails['role'];
        $store_id = $currentUserDetails['store_id'];

        $search = $request->search;
        $product_sale_returns = ProductSaleReturn::leftJoin('users','product_sale_returns.user_id','users.id')
            ->leftJoin('stores','product_sale_returns.store_id','stores.id')
            ->select(
                'product_sale_returns.id',
                'product_sale_returns.invoice_no',
                'product_sale_returns.user_id',
                'product_sale_returns.product_sale_invoice_no',
                'product_sale_returns.sub_total_amount',
                'product_sale_returns.discount_type',
                'product_sale_returns.discount_amount',
                //'product_sale_returns.total_vat_amount',
                'product_sale_returns.grand_total_amount',
                'product_sale_returns.paid_amount',
                'product_sale_returns.due_amount',
                'product_sale_returns.payment_type_id',
                'product_sale_returns.store_id',
                'product_sale_returns.customer_id',
                'product_sale_returns.created_at as date_time',
                'stores.id as store_id'
            );

        if($role !== 'Super Admin'){
            $product_sale_returns->where('product_sale_returns.store_id',$store_id);
        }

        if($search){
            $product_sale_returns->where('product_sale_returns.invoice_no','like','%'.$request->search.'%')
                ->orWhere('stores.name','like','%'.$request->search.'%');
        }

        $product_sale_data = $product_sale_returns->latest('product_sale_returns.id','desc')->paginate(12);

        if(count($product_sale_data) === 0){
            $response = APIHelpers::createAPIResponse(true,404,'No Sale Return List Found.',null);
            return response()->json($response,404);
        }else{
            return new ProductSaleReturnCollection($product_sale_data);
        }
    }

    public function productSaleReturnDetails(Request $request){
//        try {
//        $response = APIHelpers::createAPIResponse(false,200,'','come here');
//        return response()->json($response,200);
        $product_sale_details = DB::table('product_sale_returns')
            ->join('product_sale_return_details','product_sale_returns.id','product_sale_return_details.product_sale_return_id')
            ->join('products','product_sale_return_details.product_id','products.id')
            ->where('product_sale_returns.id',$request->product_sale_return_id)
            ->select(
                'product_sale_returns.store_id',
                'products.id as product_id',
                'products.name as product_name',
                'products.product_code',
                'product_sale_return_details.qty',
                'product_sale_return_details.id as product_sale_detail_id',
                'product_sale_return_details.purchase_price',
                //'product_sale_return_details.vat_amount',
                'products.product_unit_id',
                'products.product_category_id',
                'products.product_size_id',
                'products.product_sub_unit_id'
            )
            ->get();

        $sale_product = [];
        if(count($product_sale_details) > 0){
            foreach ($product_sale_details as $product_sale_detail){
                $current_stock = warehouseStoreProductCurrentStock($product_sale_detail->store_id,$product_sale_detail->product_id);
                $product = Product::find($product_sale_detail->product_id);

                $nested_data['product_id']=$product_sale_detail->product_id;
                $nested_data['product_name']=$product_sale_detail->product_name;
                $nested_data['product_code']=$product_sale_detail->product_code;
                $nested_data['product_category_id'] = $product_sale_detail->product_category_id;
                $nested_data['product_category_name'] = $product->category->name;
                $nested_data['product_unit_id'] = $product_sale_detail->product_unit_id;
                $nested_data['product_unit_name'] = $product->unit->name;
                $nested_data['product_sub_unit_id']=$product_sale_detail->product_sub_unit_id;
                $nested_data['product_sub_unit_name']=$product_sale_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                $nested_data['product_size_id'] = $product_sale_detail->product_size_id;
                $nested_data['product_size_name'] = $product_sale_detail->product_size_id ? $product->size->name : '';
                $nested_data['qty']=$product_sale_detail->qty;
                $nested_data['product_sale_detail_id']=$product_sale_detail->product_sale_detail_id;
                $nested_data['purchase_price']=$product_sale_detail->purchase_price;
                //$nested_data['vat_amount']=$product_sale_detail->vat_amount;
                $nested_data['current_stock']=$current_stock;

                array_push($sale_product, $nested_data);
            }
        }

        if($product_sale_details === null){
            $response = APIHelpers::createAPIResponse(true,404,'No Product POS Sale Detail Found.',null);
            return response()->json($response,404);
        }else{
            $response = APIHelpers::createAPIResponse(false,200,'',$sale_product);
            return response()->json($response,200);
        }
//        } catch (\Exception $e) {
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }


    public function productSaleReturnDetailsPrint(Request $request){

//        try {
            $product_sale_details = DB::table('product_sale_returns')
                ->join('product_sale_return_details','product_sale_returns.id','product_sale_return_details.product_sale_return_id')
                ->join('products','product_sale_return_details.product_id','products.id')
                ->where('product_sale_returns.id',$request->product_sale_return_id)
                ->select(
                    'product_sale_returns.store_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_code',
                    'product_sale_return_details.qty',
                    'product_sale_return_details.id as product_sale_return_detail_id',
                    'product_sale_return_details.purchase_price',
                    //'product_sale_return_details.vat_amount',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id'
                )
                ->get();

            $sale_product = [];
            if(count($product_sale_details) > 0){
                foreach ($product_sale_details as $product_sale_detail){
                    $current_stock = warehouseStoreProductCurrentStock($product_sale_detail->store_id,$product_sale_detail->product_id);
                    $product = Product::find($product_sale_detail->product_id);

                    $nested_data['product_id']=$product_sale_detail->product_id;
                    $nested_data['product_name']=$product_sale_detail->product_name;
                    $nested_data['product_code']=$product_sale_detail->product_code;
                    $nested_data['product_category_id'] = $product_sale_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $product_sale_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$product_sale_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$product_sale_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $product_sale_detail->product_size_id;
                    $nested_data['product_size_name'] = $product_sale_detail->product_size_id ? $product->size->name : '';
                    $nested_data['qty']=$product_sale_detail->qty;
                    $nested_data['product_sale_return_detail_id']=$product_sale_detail->product_sale_return_detail_id;
                    $nested_data['purchase_price']=$product_sale_detail->purchase_price;
                    //$nested_data['vat_amount']=$product_sale_detail->vat_amount;
                    $nested_data['current_stock']=$current_stock;

                    array_push($sale_product, $nested_data);
                }
                $customer_details = DB::table('product_sale_returns')
                    ->join('customers','product_sale_returns.customer_id','customers.id')
                    ->join('stores','product_sale_returns.store_id','stores.id')
                    ->where('product_sale_returns.id',$request->product_sale_return_id)
                    ->select(
                        'customers.id as customer_id',
                        'customers.name as customer_name',
                        'customers.phone as customer_phone',
                        'customers.address as customer_address',
                        'stores.id as store_id',
                        'stores.name as store_name',
                        'stores.phone as store_phone',
                        'stores.address as store_address'
                    )
                    ->first();

                return response()->json(['success' => true,'code' => 200,'data' => $sale_product, 'info' => $customer_details], 200);
            }else{
                $response = APIHelpers::createAPIResponse(true,404,'No Sale Found.',null);
                return response()->json($response,404);
            }
//        } catch (\Exception $e) {
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }

    }

    public function productSearchForSaleByStoreId(Request $request){
//        try {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'product_category_id'=> 'required',
            'product_unit_id'=> 'required',
            'product_size_id'=> 'required',
            'store_id'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }


        $product_info = productSearchForSaleByStoreId($request->store_id,$request->type,$request->product_category_id,$request->product_size_id,$request->product_unit_id,$request->product_sub_unit_id,$request->product_code);

        if(count($product_info) === 0){
            $response = APIHelpers::createAPIResponse(true,404,'No Store Product Found.',null);
            return response()->json($response,404);
        }else{
            $response = APIHelpers::createAPIResponse(false,200,'',$product_info);
            return response()->json($response,200);
        }
//        } catch (\Exception $e) {
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }


}
