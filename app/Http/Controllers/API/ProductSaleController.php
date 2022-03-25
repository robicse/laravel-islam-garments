<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\Customer;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Product;
use App\ProductSale;
use App\ProductSaleDetail;
use App\Stock;
use App\VoucherType;
use App\WarehouseStoreCurrentStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductSaleController extends Controller
{
    public function productWholeSaleCreate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'customer_id'=> 'required',
                'store_id'=> 'required',
                'paid_amount'=> 'required',
                'due_amount'=> 'required',
                'grand_total_amount'=> 'required',
                'payment_type_id'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $get_invoice_no = ProductSale::latest()->pluck('invoice_no')->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("sale-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 600000;
            }
            $final_invoice = 'sale-'.$invoice_no;

            $date = date('Y-m-d');
            $date_time = $date." ".date('h:i:s');

            $user_id = Auth::user()->id;
            $store_id = $request->store_id;
            $customer_id = $request->customer_id;
            $discount_amount = $request->discount_amount;
            $payment_type_id = $request->payment_type_id;
            $grand_total_amount = $request->grand_total_amount;
            $less_amount = $request->less_amount ? $request->less_amount : 0;
            $after_less_amount = $request->after_less_amount ? $request->after_less_amount : 0;
            $paid_amount = $request->paid_amount;
            $due_amount = $request->due_amount;
            $products = json_decode($request->products);

            // product purchase
            $productSale = new ProductSale();
            $productSale->invoice_no = $final_invoice;
            $productSale->user_id = $user_id;
            $productSale->store_id = $store_id;
            $productSale->warehouse_id = NULL;
            $productSale->customer_id = $customer_id;
            $productSale->sale_type = 'Whole Sale';
            $productSale->payment_type_id = $payment_type_id;
            $productSale->cheque_date= $request->cheque_date;
            $productSale->cheque_approved_status = $payment_type_id == '2' ? 'Pending' : NULL;
            $productSale->sub_total_amount = $request->sub_total_amount;
            $productSale->discount_type = $request->discount_type ? $request->discount_type : NULL;
            $productSale->discount_percent = $request->discount_percent ? $request->discount_percent : 0;
            $productSale->discount_amount = $request->discount_amount ? $request->discount_amount : 0;
            $productSale->after_discount_amount = $request->after_discount_amount ? $request->after_discount_amount : 0;
            $productSale->less_amount = $less_amount;
            $productSale->after_less_amount = $after_less_amount;
            $productSale->paid_amount = $paid_amount;
            $productSale->due_amount = $due_amount;
            $productSale->grand_total_amount = $grand_total_amount;
            $productSale->sale_date = $date;
            $productSale->sale_date_time = $date_time;
            $productSale->save();
            $insert_id = $productSale->id;

            $sum_total_amount = 0;
            foreach ($products as $data) {
                $price = $data->purchase_price;
                $qty = $data->qty;
                $sum_total_amount += (float)$price * (float)$qty;
            }

            if($insert_id)
            {
                // for live testing
                foreach ($products as $data) {
                    $product_id =  $data->id;
                    $price =  $data->purchase_price;
                    $product = Product::where('id',$product_id)->first();

                    // discount start
                    $final_discount_amount = $discount_amount;
                    $sub_total_amount = (float)$price * (float)$qty;
                    $amount = $final_discount_amount*$sub_total_amount;
                    $discount = $amount/$sum_total_amount;
                    // discount end

                    // vat and sub total start
                    $after_discount_amount = $sub_total_amount - $discount;
                    $sub_total = $after_discount_amount;
                    // vat and sub total end

                    // product sale detail
                    $product_sale_detail = new ProductSaleDetail();
                    $product_sale_detail->product_sale_id = $insert_id;
                    $product_sale_detail->product_id = $product_id;
                    $product_sale_detail->purchase_price = $price;
                    $product_sale_detail->qty = $qty;
                    $product_sale_detail->price = $price;
                    $product_sale_detail->discount = $discount;
                    $product_sale_detail->vat_amount = 0;
                    $product_sale_detail->sub_total = $sub_total;
                    $product_sale_detail->barcode = $product->barcode;
                    $product_sale_detail->sale_date = $date;
                    $product_sale_detail->save();

                    $check_previous_stock = Stock::where('store_id',$store_id)
                        ->where('stock_where','store')
                        ->where('product_id',$product_id)
                        ->latest()
                        ->pluck('current_stock')
                        ->first();
                    if(!empty($check_previous_stock)){
                        $previous_stock = $check_previous_stock;
                    }else{
                        $previous_stock = 0;
                    }

                    // product stock
                    $stock = new Stock();
                    $stock->ref_id = $insert_id;
                    $stock->user_id = $user_id;
                    $stock->warehouse_id = NULL;
                    $stock->store_id = $store_id;
                    $stock->product_id = $product_id;
                    $stock->product_name = $product->name;
                    $stock->stock_type = 'Whole Sale';
                    $stock->stock_where = 'Store';
                    $stock->stock_in_out = 'stock_out';
                    $stock->previous_stock = $previous_stock;
                    $stock->stock_in = 0;
                    $stock->stock_out = $qty;
                    $stock->current_stock = $previous_stock - $data->qty;
                    $stock->stock_date = $date;
                    $stock->stock_date_time = $date_time;
                    $stock->save();

                    // warehouse current stock
                    $update_warehouse_store_current_stock = WarehouseStoreCurrentStock::where('store_id',$store_id)
                        ->where('product_id',$product_id)
                        ->first();

                    $exists_current_stock = $update_warehouse_store_current_stock->current_stock;
                    $final_warehouse_store_current_stock = $exists_current_stock - $qty;
                    $update_warehouse_store_current_stock->current_stock=$final_warehouse_store_current_stock;
                    $update_warehouse_store_current_stock->save();

                }

                // posting
                $month = date('m', strtotime($date));
                $year = date('Y', strtotime($date));
                $transaction_date_time = date('Y-m-d H:i:s');

                // customer head
                $code = Customer::where('id',$customer_id)->pluck('code')->first();
                $customer_chart_of_account_info = ChartOfAccount::where('name_code',$code)->first();

                // Cash In Hand For Paid Amount
                $get_voucher_name = VoucherType::where('id',2)->pluck('name')->first();
                $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',2)->latest()->pluck('voucher_no')->first();
                if(!empty($get_voucher_no)){
                    $get_voucher_name_str = $get_voucher_name."-";
                    $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                    $voucher_no = $get_voucher+1;
                }else{
                    $voucher_no = 2000;
                }
                $final_voucher_no = $get_voucher_name.'-'.$voucher_no;

                // Cash In Hand Account Info
                $cash_chart_of_account_info = ChartOfAccount::where('head_name','Cash In Hand')->first();

                // Cheque Account Info
                $cheque_chart_of_account_info = ChartOfAccount::where('head_name','Cheque')->first();

                // Account Receivable Account Info
                $account_receivable_info = ChartOfAccount::where('head_name','Account Receivable')->first();

                // Mobile Banking Account Info
                $mobile_banking_chart_of_account_info = ChartOfAccount::where('head_name','Mobile Banking')->first();

                // customer due update
                $customer = Customer::find($customer_id);
                $previous_current_total_due = $customer->current_total_due;
                $update_current_total_due = $previous_current_total_due + $due_amount;

                $customer->current_total_due = $update_current_total_due;
                $customer->save();


                // 1st theme
//                    if($payment_type_id === '1') {
//                      // For Paid Amount
//                      // Cash In Hand debit
//                      $description = $cash_chart_of_account_info->head_name. 'Store Cash In Hand Debited For Paid Amount sales';
//                      chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
//                    }
//
//                    if($payment_type_id === '2') {
//                      // For Paid Amount
//                      // Cheque debit
//                      $description = $cheque_chart_of_account_info->head_name. ' Store Cheque Debited For Paid Amount Sales';
//                      chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $cheque_chart_of_account_info->id, $cheque_chart_of_account_info->head_code, $cheque_chart_of_account_info->head_name, $cheque_chart_of_account_info->parent_head_name, $cheque_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
//                    }
//
//                    // customer credit
//                    $description = $customer_chart_of_account_info->head_name.' Customer Credited For Paid Amount Sales';
//                    chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
//
//                    // customer debit
//                    $description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
//                    chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, NULL, $paid_amound, $description, 'Approved');
//
//                    // Account Receivable credit
//                    $description = $account_receivable_info->head_name.' Credited For Paid Amount Sales';
//                    chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $account_receivable_info->id, $account_receivable_info->head_code, $account_receivable_info->head_name, $account_receivable_info->parent_head_name, $account_receivable_info->head_type, NULL, $paid_amound, $description, 'Approved');
//
//                    // For Due Amount
//                    if($due_amount > 0){
//                        // Account Receivable credit
//                        $description = $account_receivable_info->head_name.' Credited For Due Amount Sales';
//                        chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $account_receivable_info->id, $account_receivable_info->head_code, $account_receivable_info->head_name, $account_receivable_info->parent_head_name, $account_receivable_info->head_type, $due_amount, NULL, $description, 'Approved');
//
//                        // customer credit
//                        $description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
//                        chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, NULL, $due_amount, $description, 'Approved');
//                    }



                // 2nd theme

                // Inventory Account Info
                $inventory_chart_of_account_info = ChartOfAccount::where('head_name', 'Inventory')->first();

                // Product Sale Info
                $product_sale_chart_of_account_info = ChartOfAccount::where('head_name', 'Product Sale')->first();

                // Customer Debit
                $description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
                chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, $grand_total_amount, NULL, $description, 'Approved');

                //Inventory Credit
                $description = $inventory_chart_of_account_info->head_name.' Store Inventory Credited For Sales';
                chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $inventory_chart_of_account_info->id, $inventory_chart_of_account_info->head_code, $inventory_chart_of_account_info->head_name, $inventory_chart_of_account_info->parent_head_name, $inventory_chart_of_account_info->head_type, NULL, $grand_total_amount, $description, 'Approved');

                // Product Sale Credit
//                    $description = $product_sale_chart_of_account_info->head_name.' Store Product Sale Credited For Sales';
//                    chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $product_sale_chart_of_account_info->id, $product_sale_chart_of_account_info->head_code, $product_sale_chart_of_account_info->head_name, $product_sale_chart_of_account_info->parent_head_name, $product_sale_chart_of_account_info->head_type, NULL, $grand_total_amount, $description, 'Approved');

                if($payment_type_id === '1'){
                    // Cash In Hand Debit
                    $description = $cash_chart_of_account_info->head_name. 'Store Cash In Hand Debited For Paid Amount sales';
                    chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
                }

                if($payment_type_id === '2') {
                    // Cheque Debit
                    $description = $cheque_chart_of_account_info->head_name. ' Store Cheque Debited For Paid Amount Sales';
                    chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $cheque_chart_of_account_info->id, $cheque_chart_of_account_info->head_code, $cheque_chart_of_account_info->head_name, $cheque_chart_of_account_info->parent_head_name, $cheque_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Pending');
                }

                if($payment_type_id === '3') {
                    // Mobile Banking
                    $description = $mobile_banking_chart_of_account_info->head_name. ' For Paid Amount Sales';
                    chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $mobile_banking_chart_of_account_info->id, $mobile_banking_chart_of_account_info->head_code, $mobile_banking_chart_of_account_info->head_name, $cheque_chart_of_account_info->parent_head_name, $mobile_banking_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
                }

                // Customer Credit
                $description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
                chartOfAccountTransactionDetails($insert_id, $final_invoice, $user_id, 2, $final_voucher_no, 'Sales', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, NULL, $paid_amount, $description, 'Approved');

                $response = APIHelpers::createAPIResponse(false,201,'Product Whole Sale Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Whole Sale Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }


    public function productWholeSaleListSearch(Request $request){
        $user_id = Auth::user()->id;
        $currentUserDetails = currentUserDetails($user_id);
        $role = $currentUserDetails['role'];
        $store_id = $currentUserDetails['store_id'];

        $search = $request->search;
        $product_pos_sales = DB::table('product_sales')
            ->leftJoin('users', 'product_sales.user_id', 'users.id')
            ->leftJoin('customers', 'product_sales.customer_id', 'customers.id')
            ->leftJoin('stores', 'product_sales.store_id', 'stores.id')
            ->leftJoin('payment_types', 'product_sales.payment_type_id', 'payment_types.id')
            ->select(
                'product_sales.id',
                'product_sales.invoice_no',
                'product_sales.discount_type',
                'product_sales.discount_percent',
                'product_sales.discount_amount',
                'product_sales.after_discount_amount',
                'product_sales.less_amount',
                'product_sales.after_less_amount',
                'product_sales.total_vat_amount',
                'product_sales.after_discount_amount',
                'product_sales.grand_total_amount',
                'payment_types.name as payment_type',
                'product_sales.cheque_date',
                'product_sales.cheque_approved_status',
                'product_sales.paid_amount',
                'product_sales.due_amount',
                'product_sales.sale_date_time as date_time',
                'users.name as user_name',
                'customers.id as customer_id',
                'customers.name as customer_name',
                'stores.id as store_id',
                'stores.name as store_name',
                'stores.address as store_address',
                'stores.phone'
            );

        $product_pos_sales->where('product_sales.sale_type', 'Whole Sale');

        if($role !== 'Super Admin'){
            $product_pos_sales->where('product_purchases.store_id',$store_id);
        }

        if($search){
            $product_pos_sales->where('product_sales.invoice_no', 'like', '%' . $request->search . '%')
                ->orWhere('product_sales.total_amount', 'like', '%' . $request->search . '%')
                ->orWhere('customers.name', 'like', '%' . $request->search . '%');
        }

        $product_sale_data = $product_pos_sales->latest('product_sales.id', 'desc')->paginate(12);

        if($product_sale_data === null){
            $response = APIHelpers::createAPIResponse(true,404,'No Product POS SaleFound.',null);
            return response()->json($response,404);
        }else{
            $response = APIHelpers::createAPIResponse(false,200,'',$product_sale_data);
            return response()->json($response,200);
        }

    }

    public function productWholeSaleListSearchByCustomer(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
                'from_date' => 'required',
                'to_date'=> 'required'
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $user_id = Auth::user()->id;
            $currentUserDetails = currentUserDetails($user_id);
            $role = $currentUserDetails['role'];
            $store_id = $currentUserDetails['store_id'];

            $customer_id = $request->customer_id;
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $search = $request->search;

            $product_sales = ProductSale::join('customers', 'product_sales.customer_id', 'customers.id')
                ->select(
                    'product_sales.id',
                    'product_sales.invoice_no',
                    'product_sales.payment_type_id',
                    'product_sales.grand_total_amount',
                    'product_sales.sale_date_time as date_time',
                    'product_sales.user_id',
                    'customers.id as customer_id',
                    'customers.name as customer_name'
                );

            $product_sales->where('product_sales.customer_id',$customer_id)
                ->whereBetween('product_sales.sale_date', [$from_date, $to_date]);

            if($role !== 'Super Admin'){
                $product_sales->where('product_sales.store_id',$store_id);
            }

            if($search){
                $product_sales->where('product_sales.invoice_no','like','%'.$search.'%');
                $product_sales->orWhere('customers.name','like','%'.$search.'%');
            }

            $product_sales_data = $product_sales->latest('product_sales.id','desc')->paginate(12);

            $total_amount = $product_sales->sum('product_sales.grand_total_amount');

            if(count($product_sales_data) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Sale Report Found.',null);
                return response()->json($response,404);
            }else{
//                $result_data = [
//                    'sale_data' => $product_sales_data,
//                    'total_amount' => $total_amount
//                ];
//                return new CustomerSaleCollection($result_data);
                return response()->json(['success'=>true,'code' => 200,'data' => $product_sales_data,'total_amount'=>$total_amount], 200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }


    public function productWholeSaleListSearchByCustomerPrint(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
                'from_date' => 'required',
                'to_date'=> 'required'
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $user_id = Auth::user()->id;
            $currentUserDetails = currentUserDetails($user_id);
            $role = $currentUserDetails['role'];
            $store_id = $currentUserDetails['store_id'];

            $customer_id = $request->customer_id;
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $search = $request->search;

            $product_sales = ProductSale::join('customers', 'product_sales.customer_id', 'customers.id')
                ->select(
                    'product_sales.id',
                    'product_sales.invoice_no',
                    'product_sales.payment_type_id',
                    'product_sales.grand_total_amount',
                    'product_sales.sale_date_time as date_time',
                    'product_sales.user_id',
                    'customers.id as customer_id',
                    'customers.name as customer_name'
                );

            $product_sales->where('product_sales.customer_id',$customer_id)
                ->whereBetween('product_sales.sale_date', [$from_date, $to_date]);

            if($role !== 'Super Admin'){
                $product_sales->where('product_sales.store_id',$store_id);
            }

            if($search){
                $product_sales->where('product_sales.invoice_no','like','%'.$search.'%');
                $product_sales->orWhere('customers.name','like','%'.$search.'%');
            }

            $product_sales_data = $product_sales->latest('product_sales.id','desc')->get();

            $total_amount = $product_sales->sum('product_sales.grand_total_amount');

            if(count($product_sales_data) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Sale Report Found.',null);
                return response()->json($response,404);
            }else{
//                $result_data = [
//                    'sale_data' => $product_sales_data,
//                    'total_amount' => $total_amount
//                ];
//                return new CustomerSaleCollection($result_data);
                return response()->json(['success'=>true,'code' => 200,'data' => $product_sales_data,'total_amount'=>$total_amount], 200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }


    public function productSaleDetails(Request $request){
        try {
            $product_sale_details = DB::table('product_sales')
                ->join('product_sale_details','product_sales.id','product_sale_details.product_sale_id')
                ->join('products','product_sale_details.product_id','products.id')
                ->where('product_sales.id',$request->product_sale_id)
                ->select(
                    'product_sales.store_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_code',
                    'product_sale_details.qty',
                    'product_sale_details.id as product_sale_detail_id',
                    'product_sale_details.purchase_price',
                    'product_sale_details.vat_amount',
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
                    $nested_data['vat_amount']=$product_sale_detail->vat_amount;
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
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSaleDetailsPrint(Request $request){

        try {
            $product_sale_details = DB::table('product_sales')
                ->join('product_sale_details','product_sales.id','product_sale_details.product_sale_id')
                ->join('products','product_sale_details.product_id','products.id')
                ->where('product_sales.id',$request->product_sale_id)
                ->select(
                    'product_sales.store_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_code',
                    'product_sale_details.qty',
                    'product_sale_details.id as product_sale_detail_id',
                    'product_sale_details.purchase_price',
                    'product_sale_details.vat_amount',
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
                    $nested_data['vat_amount']=$product_sale_detail->vat_amount;
                    $nested_data['current_stock']=$current_stock;

                    array_push($sale_product, $nested_data);
                }
                $customer_details = DB::table('product_sales')
                    ->join('customers','product_sales.customer_id','customers.id')
                    ->join('stores','product_sales.store_id','stores.id')
                    ->where('product_sales.id',$request->product_sale_id)
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
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }

    }

    public function productSearchForSaleByStoreId(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required',
                'product_category_id'=> 'required',
                'product_unit_id'=> 'required',
                'store_id'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $product_info = productSearchForSaleByStoreId($request->store_id,$request->type,$request->product_category_id,$request->product_size_id,$request->product_unit_id,$request->product_sub_unit_id,$request->product_code);

            if(empty($product_info)){
                $response = APIHelpers::createAPIResponse(true,404,'No Store Product Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$product_info);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
