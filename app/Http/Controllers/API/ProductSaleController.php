<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Customer;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Product;
use App\ProductSale;
use App\ProductSaleDetail;
use App\Stock;
use App\VoucherType;
use App\WarehouseStoreCurrentStock;
use Illuminate\Database\Eloquent\Model;
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

            $date = $request->date;
            $date_time = $date." ".date('h:i:s');

            $user_id = Auth::user()->id;
            $store_id = $request->store_id;
            $customer_id = $request->customer_id;
            $discount_amount = $request->discount_amount;
            $payment_type_id = $request->payment_type_id;
            $grand_total_amount = $request->grand_total_amount;
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
            $productSale ->payment_type_id = $payment_type_id;
            $productSale ->sub_total_amount = $request->sub_total_amount;
            $productSale ->discount_type = $request->discount_type ? $request->discount_type : NULL;
            $productSale ->discount_percent = $request->discount_percent ? $request->discount_percent : 0;
            $productSale ->discount_amount = $request->discount_amount ? $request->discount_amount : 0;
            $productSale ->after_discount_amount = $request->after_discount_amount ? $request->after_discount_amount : 0;
            $productSale ->paid_amount = $paid_amount;
            $productSale ->due_amount = $due_amount;
            $productSale ->grand_total_amount = $grand_total_amount;
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
                    $qty =  $data->qty;
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
                    $product_sale_detail->purchase_price = $product->purchase_price;
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

                // customer due update
                $customer = Customer::find($customer_id);
                $previous_current_total_due = $customer->current_total_due;
                $update_current_total_due = $previous_current_total_due + $due_amount;

                $customer->current_total_due = $update_current_total_due;
                $customer->save();

                $chart_of_account_transactions = new ChartOfAccountTransaction();
                $chart_of_account_transactions->ref_id = $insert_id;
                $chart_of_account_transactions->user_id = $user_id;
                $chart_of_account_transactions->warehouse_id = NULL;
                $chart_of_account_transactions->store_id = $store_id;
                $chart_of_account_transactions->payment_type_id = $payment_type_id;
                $chart_of_account_transactions->transaction_type = 'Sales';
                $chart_of_account_transactions->voucher_type_id = 2;
                $chart_of_account_transactions->voucher_no = $final_voucher_no;
                $chart_of_account_transactions->is_approved = 'approved';
                $chart_of_account_transactions->transaction_date = $date;
                $chart_of_account_transactions->transaction_date_time = $transaction_date_time;
                $chart_of_account_transactions->save();
                $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;

                if($chart_of_account_transactions_insert_id){

                    // 1st theme
//                    if($payment_type_id === '1') {
//                        // For Paid Amount
//                        // Cash In Hand debit
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = NULL;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $cash_chart_of_account_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = $paid_amount;
//                        $chart_of_account_transaction_details->credit = NULL;
//                        $chart_of_account_transaction_details->description = $cash_chart_of_account_info->head_name.' Debited For Sales';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//                    }
//
//                    if($payment_type_id === '2') {
//                        // For Paid Amount
//                        // Cheque debit
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = NULL;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $cheque_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $cheque_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $cheque_chart_of_account_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cheque_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $cheque_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = $paid_amount;
//                        $chart_of_account_transaction_details->credit = NULL;
//                        $chart_of_account_transaction_details->description = $cheque_chart_of_account_info->head_name.' Debited For Sales';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//                    }
//
//                    // customer credit
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = NULL;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
//                    $chart_of_account_transaction_details->debit = NULL;
//                    $chart_of_account_transaction_details->credit = $paid_amount;
//                    $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Supplier Credited For Paid Amount Sales';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();
//
//                    // customer debit
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = NULL;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
//                    $chart_of_account_transaction_details->debit = $paid_amount;
//                    $chart_of_account_transaction_details->credit = NULL;
//                    $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();
//
//                    // Account Receivable credit
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = NULL;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $account_receivable_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $account_receivable_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = $account_receivable_info->head_name;
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $account_receivable_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $account_receivable_info->head_type;
//                    $chart_of_account_transaction_details->debit = NULL;
//                    $chart_of_account_transaction_details->credit = $paid_amount;
//                    $chart_of_account_transaction_details->description = $account_receivable_info->head_name.' Credited For Paid Amount Sales';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();
//
//                    // For Due Amount
//                    if($due_amount > 0){
//                        // Account Receivable credit
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = NULL;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $account_receivable_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $account_receivable_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $account_receivable_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $account_receivable_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $account_receivable_info->head_type;
//                        $chart_of_account_transaction_details->debit = $due_amount;
//                        $chart_of_account_transaction_details->credit = NULL;
//                        $chart_of_account_transaction_details->description = $account_receivable_info->head_name.' Credited For Due Amount Sales';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//
//                        // customer credit
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = NULL;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = NULL;
//                        $chart_of_account_transaction_details->credit = $due_amount;
//                        $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Supplier Credited For Due Amount Sales';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//                    }



                    // 2nd theme

                    // Inventory Account Info
                    $inventory_chart_of_account_info = ChartOfAccount::where('head_name', 'Inventory')->first();

                    // Product Sale Info
                    $product_sale_chart_of_account_info = ChartOfAccount::where('head_name', 'Product Sale')->first();

                    // Customer Debit
                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                    $chart_of_account_transaction_details->warehouse_id = NULL;
                    $chart_of_account_transaction_details->store_id = $store_id;
                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                    $chart_of_account_transaction_details->chart_of_account_id = $customer_chart_of_account_info->id;
                    $chart_of_account_transaction_details->chart_of_account_number = $customer_chart_of_account_info->head_code;
                    $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
                    $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
                    $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
                    $chart_of_account_transaction_details->debit = $grand_total_amount;
                    $chart_of_account_transaction_details->credit = NULL;
                    $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
                    $chart_of_account_transaction_details->year = $year;
                    $chart_of_account_transaction_details->month = $month;
                    $chart_of_account_transaction_details->transaction_date = $date;
                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transaction_details->save();

                    //Inventory Credit
                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                    $chart_of_account_transaction_details->warehouse_id = NULL;
                    $chart_of_account_transaction_details->store_id = $store_id;
                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                    $chart_of_account_transaction_details->chart_of_account_id = $inventory_chart_of_account_info->id;
                    $chart_of_account_transaction_details->chart_of_account_number = $inventory_chart_of_account_info->head_code;
                    $chart_of_account_transaction_details->chart_of_account_name = $inventory_chart_of_account_info->head_name;
                    $chart_of_account_transaction_details->chart_of_account_parent_name = $inventory_chart_of_account_info->parent_head_name;
                    $chart_of_account_transaction_details->chart_of_account_type = $inventory_chart_of_account_info->head_type;
                    $chart_of_account_transaction_details->debit = NULL;
                    $chart_of_account_transaction_details->credit = $grand_total_amount;
                    $chart_of_account_transaction_details->description = $inventory_chart_of_account_info->head_name.' Store Inventory Credited For Sales';
                    $chart_of_account_transaction_details->year = $year;
                    $chart_of_account_transaction_details->month = $month;
                    $chart_of_account_transaction_details->transaction_date = $date;
                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transaction_details->save();

                    // Product Sale Credit
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = NULL;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $product_sale_chart_of_account_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $product_sale_chart_of_account_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = $product_sale_chart_of_account_info->head_name;
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $product_sale_chart_of_account_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $product_sale_chart_of_account_info->head_type;
//                    $chart_of_account_transaction_details->debit = NULL;
//                    $chart_of_account_transaction_details->credit = $grand_total_amount;
//                    $chart_of_account_transaction_details->description = $product_sale_chart_of_account_info->head_name.' Store Product Sale Credited For Sales';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();

                    if($payment_type_id === '1'){

                        // Cash In Hand Debit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $cash_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = $paid_amount;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = $cash_chart_of_account_info->head_name. 'Store Cash In Hand Debited For Paid Amount sales';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();
                    }

                    if($payment_type_id === '2') {
                        // Cheque Debit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cheque_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cheque_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $cheque_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cheque_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $cheque_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = $paid_amount;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = $cheque_chart_of_account_info->head_name. ' Store Cheque Debited For Paid Amount Sales';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();
                    }

                    // Customer Credit
                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                    $chart_of_account_transaction_details->warehouse_id = NULL;
                    $chart_of_account_transaction_details->store_id = $store_id;
                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                    $chart_of_account_transaction_details->chart_of_account_id = $customer_chart_of_account_info->id;
                    $chart_of_account_transaction_details->chart_of_account_number = $customer_chart_of_account_info->head_code;
                    $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
                    $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
                    $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
                    $chart_of_account_transaction_details->debit = NULL;
                    $chart_of_account_transaction_details->credit = $paid_amount;
                    $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales';
                    $chart_of_account_transaction_details->year = $year;
                    $chart_of_account_transaction_details->month = $month;
                    $chart_of_account_transaction_details->transaction_date = $date;
                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transaction_details->save();


                }

                $response = APIHelpers::createAPIResponse(false,201,'Product Whole Sale Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Whole Sale Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }


    public function productWholeSaleListSearch(Request $request){
        $user_id = Auth::user()->id;
        $currentUserDetails = currentUserDetails($user_id);
        $role = $currentUserDetails['role'];
        $store_id = $currentUserDetails['store_id'];

        if($role == 'Super Admin') {
            if ($request->search) {
                $product_pos_sales = DB::table('product_sales')
                    ->leftJoin('users', 'product_sales.user_id', 'users.id')
                    ->leftJoin('customers', 'product_sales.customer_id', 'customers.id')
                    ->leftJoin('stores', 'product_sales.store_id', 'stores.id')
                    ->leftJoin('payment_types', 'product_sales.payment_type_id', 'payment_types.id')
                    ->where('product_sales.sale_type', 'Whole Sale')
                    ->where('product_sales.invoice_no', 'like', '%' . $request->search . '%')
                    ->orWhere('product_sales.total_amount', 'like', '%' . $request->search . '%')
                    ->orWhere('customers.name', 'like', '%' . $request->search . '%')
                    ->select('product_sales.id', 'product_sales.invoice_no', 'product_sales.discount_type', 'product_sales.discount_percent', 'product_sales.discount_amount', 'product_sales.total_vat_amount', 'product_sales.after_discount_amount', 'product_sales.grand_total_amount', 'payment_types.name as payment_type', 'product_sales.paid_amount', 'product_sales.due_amount', 'product_sales.sale_date_time as date_time', 'users.name as user_name', 'customers.id as customer_id', 'customers.name as customer_name', 'stores.id as store_id', 'stores.name as store_name', 'stores.address as store_address', 'stores.phone')
                    ->orderBy('product_sales.id', 'desc')
                    ->paginate(12);


            } else {
                $product_pos_sales = DB::table('product_sales')
                    ->leftJoin('users', 'product_sales.user_id', 'users.id')
                    ->leftJoin('customers', 'product_sales.customer_id', 'customers.id')
                    ->leftJoin('stores', 'product_sales.store_id', 'stores.id')
                    ->leftJoin('payment_types', 'product_sales.payment_type_id', 'payment_types.id')
                    ->where('product_sales.sale_type', 'Whole Sale')
                    ->select('product_sales.id', 'product_sales.invoice_no', 'product_sales.discount_type', 'product_sales.discount_percent', 'product_sales.discount_amount', 'product_sales.total_vat_amount', 'product_sales.after_discount_amount', 'product_sales.grand_total_amount', 'payment_types.name as payment_type', 'product_sales.paid_amount', 'product_sales.due_amount', 'product_sales.sale_date_time as date_time', 'users.name as user_name', 'customers.id as customer_id', 'customers.name as customer_name', 'stores.id as store_id', 'stores.name as store_name', 'stores.address as store_address', 'stores.phone')
                    ->orderBy('product_sales.id', 'desc')
                    ->paginate(12);
            }
        }else{
            if ($request->search) {
                $product_pos_sales = DB::table('product_sales')
                    ->leftJoin('users', 'product_sales.user_id', 'users.id')
                    ->leftJoin('customers', 'product_sales.customer_id', 'customers.id')
                    ->leftJoin('stores', 'product_sales.store_id', 'stores.id')
                    ->leftJoin('payment_types', 'product_sales.payment_type_id', 'payment_types.id')
                    ->where('product_sales.store_id',$store_id)
                    ->where('product_sales.sale_type', 'Whole Sale')
                    ->where('product_sales.invoice_no', 'like', '%' . $request->search . '%')
                    ->orWhere('product_sales.total_amount', 'like', '%' . $request->search . '%')
                    ->orWhere('customers.name', 'like', '%' . $request->search . '%')
                    ->select('product_sales.id', 'product_sales.invoice_no', 'product_sales.discount_type', 'product_sales.discount_percent', 'product_sales.discount_amount', 'product_sales.total_vat_amount', 'product_sales.after_discount_amount', 'product_sales.grand_total_amount', 'payment_types.name as payment_type', 'product_sales.paid_amount', 'product_sales.due_amount', 'product_sales.sale_date_time as date_time', 'users.name as user_name', 'customers.id as customer_id', 'customers.name as customer_name', 'stores.id as store_id', 'stores.name as store_name', 'stores.address as store_address', 'stores.phone')
                    ->orderBy('product_sales.id', 'desc')
                    ->paginate(12);


            } else {
                $product_pos_sales = DB::table('product_sales')
                    ->leftJoin('users', 'product_sales.user_id', 'users.id')
                    ->leftJoin('customers', 'product_sales.customer_id', 'customers.id')
                    ->leftJoin('stores', 'product_sales.store_id', 'stores.id')
                    ->leftJoin('payment_types', 'product_sales.payment_type_id', 'payment_types.id')
                    ->where('product_sales.store_id',$store_id)
                    ->where('product_sales.sale_type', 'Whole Sale')
                    ->select('product_sales.id', 'product_sales.invoice_no', 'product_sales.discount_type', 'product_sales.discount_percent', 'product_sales.discount_amount', 'product_sales.total_vat_amount', 'product_sales.after_discount_amount', 'product_sales.grand_total_amount', 'payment_types.name as payment_type', 'product_sales.paid_amount', 'product_sales.due_amount', 'product_sales.sale_date_time as date_time', 'users.name as user_name', 'customers.id as customer_id', 'customers.name as customer_name', 'stores.id as store_id', 'stores.name as store_name', 'stores.address as store_address', 'stores.phone')
                    ->orderBy('product_sales.id', 'desc')
                    ->paginate(12);
            }
        }

        if($product_pos_sales === null){
            $response = APIHelpers::createAPIResponse(true,404,'No Product POS SaleFound.',null);
            return response()->json($response,404);
        }else{
            $response = APIHelpers::createAPIResponse(false,200,'',$product_pos_sales);
            return response()->json($response,200);
        }

    }


    public function productSaleDetails(Request $request){
//        try {
//        $response = APIHelpers::createAPIResponse(false,200,'','come here');
//        return response()->json($response,200);
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
//        } catch (\Exception $e) {
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
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
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }

    }

    public function productSearchForSaleByStoreId(Request $request){
//        try {
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
