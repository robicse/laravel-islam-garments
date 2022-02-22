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

//        try {
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
//            return response()->json(['success'=>true,'response' => $request->all()], 200);

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
            if($payment_type_id == 1){
                $paid_amount = $grand_total_amount;
                $due_amount = 0;
            }elseif($payment_type_id == 2){
                $paid_amount = $request->paid_amount;
                $due_amount = $request->due_amount;
            }else{

            }


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

            // discount start
            $sum_total_amount = 0;
            $products = json_decode($request->products);
            foreach ($products as $data) {
                $price = $data->purchase_price;
                $qty = $data->qty;
                $sum_total_amount += (float)$price * (float)$qty;
            }
            // discount start

            if($insert_id)
            {
                // for postman testing

                // for live testing
                foreach ($products as $data) {

                    $product_id =  $data->product_id;
                    $price =  $data->purchase_price;
                    $qty =  $data->qty;


                    $product = Product::where('id',$product_id)->first();

                    // discount start
//                    $price = $data['mrp_price'];
//                    $discount_amount = $request->discount_amount;
//                    $total_amount = $request->total_amount;
//
//                    $final_discount_amount = (float)$discount_amount * (float)$price;
//                    $final_total_amount = (float)$discount_amount + (float)$total_amount;
//                    $discount_type = $request->discount_type;
//                    $discount = (float)$final_discount_amount/(float)$final_total_amount;
//                    if($discount_type != NULL){
//                        if($discount_type == 'Flat'){
//                            $discount = round($discount);
//                        }
//                    }
                    // discount end



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

                // N.B: whole sale kono paid hobe na

                // transaction
    //            $transaction = new Transaction();
    //            $transaction->ref_id = $insert_id;
    //            $transaction->invoice_no = $final_invoice;
    //            $transaction->user_id = $user_id;
    //            $transaction->warehouse_id = $warehouse_id;
    //            $transaction->store_id = NULL;
    //            $transaction->party_id = $request->party_id;
    //            $transaction->transaction_type = 'whole_sale';
    //            $transaction->payment_type = $request->payment_type;
    //            $transaction->amount = $request->paid_amount;
    //            $transaction->transaction_date = $date;
    //            $transaction->transaction_date_time = $date_time;
    //            $transaction->save();
    //            $transaction_id = $transaction->id;
    //
    //            // payment paid
    //            $payment_collection = new PaymentCollection();
    //            $payment_collection->invoice_no = $final_invoice;
    //            $payment_collection->product_sale_id = $insert_id;
    //            $payment_collection->user_id = $user_id;
    //            $payment_collection->party_id = $request->party_id;
    //            $payment_collection->warehouse_id = $warehouse_id;
    //            $payment_collection->collection_type = 'Sale';
    //            $payment_collection->collection_amount = $request->paid_amount;
    //            $payment_collection->due_amount = $request->due_amount;
    //            $payment_collection->current_collection_amount = $request->paid_amount;
    //            $payment_collection->collection_date = $date;
    //            $payment_collection->collection_date_time = $date_time;
    //            $payment_collection->save();
                // posting
                $month = date('m', strtotime($date));
                $year = date('Y', strtotime($date));
                $transaction_date_time = date('Y-m-d H:i:s');


                // Note For Cash Paid

                // 1.
                // Cash In Hand    => debit
                // Customer        => credit


                // 2.
                // Customer        => debit
                // Cash In Hand    => credit

                // supplier head
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

                // Account Receivable Account Info
                $account_receivable_info = ChartOfAccount::where('head_name','Account Receivable')->first();

                if($payment_type_id == 1){
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
                        // supplier debit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = 2;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = NULL;
                        $chart_of_account_transaction_details->credit = $grand_total_amount;
                        $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Customer Credited For Sales';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();

                        // Cash In Hand credit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = 'Cash In Hand';
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = $grand_total_amount;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = 'Cash In Hand Debit For Sales';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();

                        // Cash In Hand debit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = 'Cash In Hand';
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = NULL;
                        $chart_of_account_transaction_details->credit = $grand_total_amount;
                        $chart_of_account_transaction_details->description = 'Cash In Hand Credit For Sales';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();

                        // supplier credit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = 2;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = $grand_total_amount;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Supplier Credited For Purchases';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();
                    }
                }elseif($payment_type_id == 2){
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

                        // supplier debit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = 2;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $customer_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $customer_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $customer_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = NULL;
                        $chart_of_account_transaction_details->credit = $grand_total_amount;
                        $chart_of_account_transaction_details->description = $customer_chart_of_account_info->head_name.' Customer Credited For Sales';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();


                        // Account Receivable
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $account_receivable_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $account_receivable_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $account_receivable_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $account_receivable_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $account_receivable_info->head_type;
                        $chart_of_account_transaction_details->debit = $grand_total_amount;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = $account_receivable_info->head_name.' Debited For Sales';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();
                    }
                }


                $response = APIHelpers::createAPIResponse(false,201,'Product Whole Sale Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Whole Sale Updated Failed.',null);
                return response()->json($response,400);
            }
//        } catch (\Exception $e) {
//            //return $e->getMessage();
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }


    public function productWholeSaleListSearch(Request $request){
        if($request->search){
            $product_pos_sales = DB::table('product_sales')
                ->leftJoin('users','product_sales.user_id','users.id')
                ->leftJoin('customers','product_sales.customer_id','customers.id')
                ->leftJoin('stores','product_sales.store_id','stores.id')
                ->where('product_sales.sale_type','Whole Sale')
                ->where('product_sales.invoice_no','like','%'.$request->search.'%')
                ->orWhere('product_sales.total_amount','like','%'.$request->search.'%')
                ->orWhere('customers.name','like','%'.$request->search.'%')
                ->select('product_sales.id','product_sales.invoice_no','product_sales.discount_type','product_sales.discount_percent','product_sales.discount_amount','product_sales.total_vat_amount','product_sales.after_discount_amount','product_sales.grand_total_amount','product_sales.paid_amount','product_sales.due_amount','product_sales.sale_date_time','users.name as user_name','customers.id as customer_id','customers.name as customer_name','stores.id as store_id','stores.name as store_name','stores.address as store_address','stores.phone')
                ->orderBy('product_sales.id','desc')
                ->paginate(12);


        }else{
            $product_pos_sales = DB::table('product_sales')
                ->leftJoin('users','product_sales.user_id','users.id')
                ->leftJoin('customers','product_sales.customer_id','customers.id')
                ->leftJoin('stores','product_sales.store_id','stores.id')
                ->where('product_sales.sale_type','Whole Sale')
                ->select('product_sales.id','product_sales.invoice_no','product_sales.discount_type','product_sales.discount_percent','product_sales.discount_amount','product_sales.total_vat_amount','product_sales.after_discount_amount','product_sales.grand_total_amount','product_sales.paid_amount','product_sales.due_amount','product_sales.sale_date_time','users.name as user_name','customers.id as customer_id','customers.name as customer_name','stores.id as store_id','stores.name as store_name','stores.address as store_address','stores.phone')
                ->orderBy('product_sales.id','desc')
                ->paginate(12);
        }

        if($product_pos_sales === null){
            $response = APIHelpers::createAPIResponse(true,404,'No Product POS SaleFound.',null);
            return response()->json($response,404);
        }else{
            $response = APIHelpers::createAPIResponse(false,200,'',$product_pos_sales);
            return response()->json($response,200);
        }

    }


}
