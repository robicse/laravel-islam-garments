<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductPurchaseCollection;
use App\Http\Resources\SupplierPurchaseCollection;
use App\Product;
use App\ProductPurchase;
use App\ProductPurchaseDetail;
use App\Stock;
use App\Supplier;
use App\VoucherType;
use App\WarehouseCurrentStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductPurchaseController extends Controller
{
    public function productPurchaseCreate(Request $request){
        // try {
            $validator = Validator::make($request->all(), [
                    'date'=> 'required',
                    'warehouse_id'=> 'required',
                    //'supplier_id'=> 'required',
                    'payment_type_id'=> 'required',
                    'sub_total_amount'=> 'required',
                    'discount_percent'=> 'required',
                    'discount_amount'=> 'required',
                    'after_discount_amount'=> 'required',
                    'grand_total_amount'=> 'required',
                    'paid_amount'=> 'required',
                    'due_amount'=> 'required',
                ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $get_invoice_no = ProductPurchase::latest()->pluck('invoice_no')->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("purchase-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1000;
            }
            $final_invoice = 'purchase-'.$invoice_no;

            $date = $request->date ? $request->date : date('Y-m-d');
            $date_time = $request->date ? $request->date.date(' h:i:s') : date('Y-m-d h:i:s');

            $user_id = Auth::user()->id;
            $supplier_invoice_no=$request->supplier_invoice_no;
            $supplier_id=$request->supplier_id ? $request->supplier_id : 1;
            $warehouse_id = $request->warehouse_id;
            $store_id = NULL;
            $payment_type_id = $request->payment_type_id;
            $cheque_date = $request->cheque_date;
            $sub_total_amount = $request->sub_total_amount;
            $grand_total_amount = $request->grand_total_amount;
            $discount_type = $request->discount_type ? $request->discount_type : NULL;
            $discount_percent = $request->discount_percent ? $request->discount_percent : 0;
            $discount_amount = $request->discount_amount ? $request->discount_amount : 0;
            $after_discount_amount = $request->after_discount_amount ? $request->after_discount_amount : 0;
            $less_amount = $request->less_amount ? $request->less_amount : 0;
            $after_less_amount = $request->after_less_amount ? $request->after_less_amount : 0;
            $paid_amount = $request->paid_amount;
            $due_amount = $request->due_amount;
            $products = json_decode($request->products);


            // Buy and Own Product Check For Same Supplier
            $check_type = '';
            $count = count($products);
            for ($i=0; $i<$count; $i++) {
                $item = $products[$i];
                $check_type = $item->type;
                $lastFrom = null;
                if ($i > 0) {
                    $lastFrom = $products[$i-1]->type;
                    if ($item->type !== $lastFrom) {
                        $response = APIHelpers::createAPIResponse(true,409,'Own And Buy Product Not In Same Supplier Yet!',null);
                        return response()->json($response,409);
                    }
                }
            }


            // product purchase
            $productPurchase = new ProductPurchase();
            $productPurchase ->invoice_no = $final_invoice;
            $productPurchase ->supplier_invoice_no = $supplier_invoice_no ? $supplier_invoice_no : NULL;
            $productPurchase ->user_id = $user_id;
            $productPurchase ->supplier_id = $supplier_id;
            $productPurchase ->warehouse_id = $warehouse_id;
            $productPurchase ->payment_type_id = $payment_type_id;
            $productPurchase ->cheque_date = $cheque_date ? $cheque_date : NULL;
            $productPurchase ->sub_total_amount = $sub_total_amount;
            $productPurchase ->discount_type = $discount_type;
            $productPurchase ->discount_percent = $discount_percent;
            $productPurchase ->discount_amount = $discount_amount;
            $productPurchase ->after_discount_amount = $after_discount_amount;
            $productPurchase ->less_amount = $less_amount;
            $productPurchase ->after_less_amount = $after_less_amount;
            $productPurchase ->paid_amount = $paid_amount;
            $productPurchase ->due_amount = $due_amount;
            $productPurchase ->grand_total_amount = $grand_total_amount;
            $productPurchase ->purchase_date = $date;
            $productPurchase ->purchase_date_time = $date_time;
            $productPurchase->save();
            $insert_id = $productPurchase->id;

            if($insert_id)
            {
                // for postman also api workable
                foreach ($products as $data) {
                    $product_id =  $data->id;

                    $product_code =  $data->product_code;
                    $purchase_price =  $data->purchase_price;
                    $qty =  $data->qty;
                    $product = Product::where('id',$product_id)->first();

                    // product purchase detail
                    $purchase_purchase_detail = new ProductPurchaseDetail();
                    $purchase_purchase_detail->product_purchase_id = $insert_id;
                    $purchase_purchase_detail->product_id = $product_id;
                    $purchase_purchase_detail->product_type = $product->type;
                    $purchase_purchase_detail->product_name = $product->product_name;
                    $purchase_purchase_detail->barcode = $product->barcode;
                    $purchase_purchase_detail->product_code = $product_code;
                    $purchase_purchase_detail->qty = $qty;
                    $purchase_purchase_detail->purchase_price = $purchase_price;
                    $purchase_purchase_detail->mrp_price = $purchase_price;
                    $purchase_purchase_detail->sub_total = $qty*$purchase_price;
                    $purchase_purchase_detail->save();

                    $check_previous_stock = Stock::where('product_id',$product_id)->latest()->pluck('current_stock')->first();
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
                   $stock->product_name = $product->name;
                   $stock->product_type = $product->type;
                   $stock->stock_type = 'Whole Purchase';
                   $stock->stock_where = 'warehouse';
                   $stock->stock_in_out = 'stock_in';
                   $stock->previous_stock = $previous_stock;
                   $stock->stock_in = $qty;
                   $stock->stock_out = 0;
                   $stock->current_stock = $previous_stock + $qty;
                   $stock->stock_date = $date;
                   $stock->stock_date_time = $date_time;
                   $stock->save();

                    // $current_stock = $previous_stock + $qty;
                    // stock($insert_id,$user_id,$warehouse_id,$store_id,$product,'Whole Purchase','Warehouse','Stock In',$previous_stock,0,$current_stock,$date,$date_time);

                    // warehouse current stock
                    $check_exists_warehouse_current_stock = WarehouseCurrentStock::where('warehouse_id',$warehouse_id)
                        ->where('product_id',$product_id)
                        ->first();
                    if($check_exists_warehouse_current_stock){
                        $warehouse_current_stock_update = WarehouseCurrentStock::find($check_exists_warehouse_current_stock->id);
                        $warehouse_current_stock_update->current_stock=$check_exists_warehouse_current_stock->current_stock + $qty;
                        $warehouse_current_stock_update->save();
                    }else{
                        $warehouse_current_stock = new WarehouseCurrentStock();
                        $warehouse_current_stock->warehouse_id=$warehouse_id;
                        $warehouse_current_stock->product_id=$product_id;
                        $warehouse_current_stock->current_stock=$qty;
                        $warehouse_current_stock->save();
                    }
                }

                if($check_type == 'Buy'){
                    // posting
                    $month = date('m', strtotime($date));
                    $year = date('Y', strtotime($date));
                    $transaction_date_time = date('Y-m-d H:i:s');

                    // supplier current due update
                    $supplier = Supplier::find($supplier_id);
                    $previous_current_total_due = $supplier->current_total_due;
                    $update_current_total_due = $previous_current_total_due + $due_amount;

                    $supplier->current_total_due = $update_current_total_due;
                    $supplier->save();

                    // Cash In Hand For Paid Amount
                    $get_voucher_name = VoucherType::where('id',1)->pluck('name')->first();
                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',1)->latest()->pluck('voucher_no')->first();
                    if(!empty($get_voucher_no)){
                        $get_voucher_name_str = $get_voucher_name."-";
                        $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                        $voucher_no = $get_voucher+1;
                    }else{
                        $voucher_no = 1000;
                    }
                    $final_voucher_no = $get_voucher_name.'-'.$voucher_no;
                    $chart_of_account_transactions = new ChartOfAccountTransaction();
                    $chart_of_account_transactions->ref_id = $insert_id;
                    $chart_of_account_transactions->user_id = $user_id;
                    $chart_of_account_transactions->warehouse_id = $warehouse_id;
                    $chart_of_account_transactions->store_id = $store_id;
                    $chart_of_account_transactions->payment_type_id = $payment_type_id;
                    $chart_of_account_transactions->transaction_type = 'Purchases';
                    $chart_of_account_transactions->voucher_type_id = 1;
                    $chart_of_account_transactions->voucher_no = $final_voucher_no;
                    $chart_of_account_transactions->is_approved = 'approved';
                    $chart_of_account_transactions->transaction_date = $date;
                    $chart_of_account_transactions->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transactions->save();
                    $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;

                    if($chart_of_account_transactions_insert_id){

                        // Cash In Hand Account Info
                        $cash_chart_of_account_info = ChartOfAccount::where('head_name','Cash In Hand')->first();

                        // Cheque Account Info
                        $cheque_chart_of_account_info = ChartOfAccount::where('head_name','Cheque')->first();

                        // supplier head
                        $code = Supplier::where('id',$supplier_id)->pluck('code')->first();
                        $supplier_chart_of_account_info = ChartOfAccount::where('name_code',$code)->first();

                        // Account Payable Account Info
                        $account_payable_info = ChartOfAccount::where('head_name','Account Payable')->first();

                        // 1st theme
                        // For Paid Amount
                        // supplier debit
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $supplier_chart_of_account_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $supplier_chart_of_account_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = $supplier_chart_of_account_info->head_name;
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $supplier_chart_of_account_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $supplier_chart_of_account_info->head_type;
//                    $chart_of_account_transaction_details->debit = $paid_amount;
//                    $chart_of_account_transaction_details->credit = NULL;
//                    $chart_of_account_transaction_details->description = $supplier_chart_of_account_info->head_name.' Supplier Debited For Paid Amount Purchases';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();

//                    if($payment_type_id === '1'){
//
//                        // Cash In Hand credit
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $cash_chart_of_account_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = NULL;
//                        $chart_of_account_transaction_details->credit = $paid_amount;
//                        $chart_of_account_transaction_details->description = $cash_chart_of_account_info->head_name. ' Credit For Paid Amount Purchases';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//                    }
//
//                    if($payment_type_id === '2') {
//                        // Cheque
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $cheque_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $cheque_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $cheque_chart_of_account_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cheque_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $cheque_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = NULL;
//                        $chart_of_account_transaction_details->credit = $paid_amount;
//                        $chart_of_account_transaction_details->description = $cheque_chart_of_account_info->head_name. ' For Paid Amount Purchases';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//                    }
//
//                    // Account Payable
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $account_payable_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $account_payable_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = $account_payable_info->head_name;
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $account_payable_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $account_payable_info->head_type;
//                    $chart_of_account_transaction_details->debit = $paid_amount;
//                    $chart_of_account_transaction_details->credit = NULL;
//                    $chart_of_account_transaction_details->description = $account_payable_info->head_name.' Debit For Paid Amount Purchases';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();
//
//                    // supplier credit
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $supplier_chart_of_account_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $supplier_chart_of_account_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = $supplier_chart_of_account_info->head_name;
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $supplier_chart_of_account_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $supplier_chart_of_account_info->head_type;
//                    $chart_of_account_transaction_details->debit = NULL;
//                    $chart_of_account_transaction_details->credit = $paid_amount;
//                    $chart_of_account_transaction_details->description = $supplier_chart_of_account_info->head_name.' Supplier Credited For Paid Amount Purchases';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();
//
//                    // For Due Amount
//                    if($due_amount > 0){
//                        // supplier debit
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $supplier_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $supplier_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $supplier_chart_of_account_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $supplier_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $supplier_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = $due_amount;
//                        $chart_of_account_transaction_details->credit = NULL;
//                        $chart_of_account_transaction_details->description = $supplier_chart_of_account_info->head_name.' Supplier Debited For Due Amount Purchases';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//
//                        // Account Payable
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                        $chart_of_account_transaction_details->store_id = $store_id;
//                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $account_payable_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $account_payable_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $account_payable_info->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $account_payable_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $account_payable_info->head_type;
//                        $chart_of_account_transaction_details->debit = NULL;
//                        $chart_of_account_transaction_details->credit = $due_amount;
//                        $chart_of_account_transaction_details->description = $account_payable_info->head_name.' Credited For Purchases Due Amount Purchases';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                        $chart_of_account_transaction_details->save();
//                    }




                        // 2nd theme
                        // Inventory Account Info
                        $inventory_chart_of_account_info = ChartOfAccount::where('head_name', 'Inventory')->first();

                        //Inventory Debit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                        $chart_of_account_transaction_details->store_id = NULL;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $inventory_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $inventory_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $inventory_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $inventory_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $inventory_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = $grand_total_amount;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = $inventory_chart_of_account_info->head_name.' Warehouse Inventory Debit For Purchases';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();

                        // Supplier Credit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $supplier_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $supplier_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $supplier_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $supplier_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $supplier_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = NULL;
                        $chart_of_account_transaction_details->credit = $grand_total_amount;
                        $chart_of_account_transaction_details->description = $supplier_chart_of_account_info->head_name.' Supplier Credited For Purchases';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();

                        // Supplier Debit
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $supplier_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $supplier_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $supplier_chart_of_account_info->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $supplier_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $supplier_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = $paid_amount;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = $supplier_chart_of_account_info->head_name.' Supplier Debited For Paid Amount Purchases';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();

                        if($payment_type_id === '1'){

                            // Cash In Hand credit
                            $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                            $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                            $chart_of_account_transaction_details->store_id = $store_id;
                            $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                            $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                            $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                            $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                            $chart_of_account_transaction_details->chart_of_account_name = $cash_chart_of_account_info->head_name;
                            $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
                            $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
                            $chart_of_account_transaction_details->debit = NULL;
                            $chart_of_account_transaction_details->credit = $paid_amount;
                            $chart_of_account_transaction_details->description = $cash_chart_of_account_info->head_name. ' Credit For Paid Amount Purchases';
                            $chart_of_account_transaction_details->year = $year;
                            $chart_of_account_transaction_details->month = $month;
                            $chart_of_account_transaction_details->transaction_date = $date;
                            $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                            $chart_of_account_transaction_details->save();
                        }

                        if($payment_type_id === '2') {
                            // Cheque
                            $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                            $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                            $chart_of_account_transaction_details->store_id = $store_id;
                            $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                            $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                            $chart_of_account_transaction_details->chart_of_account_id = $cheque_chart_of_account_info->id;
                            $chart_of_account_transaction_details->chart_of_account_number = $cheque_chart_of_account_info->head_code;
                            $chart_of_account_transaction_details->chart_of_account_name = $cheque_chart_of_account_info->head_name;
                            $chart_of_account_transaction_details->chart_of_account_parent_name = $cheque_chart_of_account_info->parent_head_name;
                            $chart_of_account_transaction_details->chart_of_account_type = $cheque_chart_of_account_info->head_type;
                            $chart_of_account_transaction_details->debit = NULL;
                            $chart_of_account_transaction_details->credit = $paid_amount;
                            $chart_of_account_transaction_details->description = $cheque_chart_of_account_info->head_name. ' For Paid Amount Purchases';
                            $chart_of_account_transaction_details->year = $year;
                            $chart_of_account_transaction_details->month = $month;
                            $chart_of_account_transaction_details->transaction_date = $date;
                            $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                            $chart_of_account_transaction_details->save();
                        }


                    }
                }

                $response = APIHelpers::createAPIResponse(false,201,'Product Purchase Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Purchase Updated Failed.',null);
                return response()->json($response,400);
            }
        // } catch (\Exception $e) {
        //     //return $e->getMessage();
        //     $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
        //     return response()->json($response,500);
        // }
    }

//    public function productPurchaseList(){
//        $product_purchases = DB::table('product_purchases')
//            ->leftJoin('users','product_purchases.user_id','users.id')
//            ->leftJoin('suppliers','product_purchases.supplier_id','suppliers.id')
//            ->leftJoin('warehouses','product_purchases.warehouse_id','warehouses.id')
//            ->select('product_purchases.id','product_purchases.invoice_no','product_purchases.discount_type','product_purchases.discount_amount','product_purchases.grand_total_amount','product_purchases.paid_amount','product_purchases.due_amount','product_purchases.purchase_date_time','users.name as user_name','suppliers.id as supplier_id','suppliers.name as supplier_name','warehouses.id as warehouse_id','warehouses.name as warehouse_name')
//            ->orderBy('product_purchases.id','desc')
//            ->get();
//
//        if(count($product_purchases) > 0)
//        {
//            $product_purchase_arr = [];
//            foreach ($product_purchases as $data){
//
//                $nested_data['id']=$data->id;
//                $nested_data['invoice_no']=ucfirst($data->invoice_no);
//                $nested_data['discount_type']=$data->discount_type;
//                $nested_data['discount_amount']=$data->discount_amount;
//                $nested_data['grand_total_amount']=$data->grand_total_amount;
//                $nested_data['paid_amount']=$data->paid_amount;
//                $nested_data['due_amount']=$data->due_amount;
//                $nested_data['purchase_date_time']=$data->purchase_date_time;
//                $nested_data['user_name']=$data->user_name;
//                $nested_data['supplier_id']=$data->supplier_id;
//                $nested_data['supplier_name']=$data->supplier_name;
//                $nested_data['warehouse_id']=$data->warehouse_id;
//                $nested_data['warehouse_name']=$data->warehouse_name;
//
//                array_push($product_purchase_arr,$nested_data);
//            }
//
//            $response = APIHelpers::createAPIResponse(false,200,'',$product_purchase_arr);
//            return response()->json($response,200);
//        }else{
//            $response = APIHelpers::createAPIResponse(true,404,'No Product List Found.',null);
//            return response()->json($response,404);
//        }
//    }

    public function productPurchaseListPaginationWithSearch(Request $request){
        try {
            $user_id = Auth::user()->id;
            $currentUserDetails = currentUserDetails($user_id);
            $role = $currentUserDetails['role'];
            $warehouse_id = $currentUserDetails['warehouse_id'];

            if($role == 'Super Admin'){
                if($request->search){
                    $product_purchases = ProductPurchase::join('suppliers','product_purchases.supplier_id','suppliers.id')
                        //                    ->where('product_purchases.purchase_type','whole_purchase')
                        //                    ->where(function ($q) use ($request){
                        //                        $q->where('product_purchases.invoice_no','like','%'.$request->search.'%')
                        //                            ->orWhere('suppliers.name','like','%'.$request->search.'%');
                        //                    })
                        ->where('product_purchases.invoice_no','like','%'.$request->search.'%')
                        ->orWhere('suppliers.name','like','%'.$request->search.'%')
                        ->select(
                            'product_purchases.id',
                            'product_purchases.invoice_no',
                            'product_purchases.payment_type_id',
                            'product_purchases.sub_total_amount',
                            'product_purchases.discount_type',
                            'product_purchases.discount_percent',
                            'product_purchases.discount_amount',
                            'product_purchases.grand_total_amount',
                            'product_purchases.paid_amount',
                            'product_purchases.due_amount',
                            'product_purchases.purchase_date_time as date_time',
                            'product_purchases.user_id',
                            'product_purchases.supplier_id',
                            'product_purchases.warehouse_id'
                        )
                        ->latest('product_purchases.id','desc')->paginate(12);

                }else{
                    //$product_purchases = ProductPurchase::latest()->paginate(12);
                    $product_purchases = ProductPurchase::join('suppliers','product_purchases.supplier_id','suppliers.id')
                        ->select(
                            'product_purchases.id',
                            'product_purchases.invoice_no',
                            'product_purchases.payment_type_id',
                            'product_purchases.sub_total_amount',
                            'product_purchases.discount_type',
                            'product_purchases.discount_percent',
                            'product_purchases.discount_amount',
                            'product_purchases.grand_total_amount',
                            'product_purchases.paid_amount',
                            'product_purchases.due_amount',
                            'product_purchases.purchase_date_time as date_time',
                            'product_purchases.user_id',
                            'product_purchases.supplier_id',
                            'product_purchases.warehouse_id'
                        )
                        ->latest('product_purchases.id','desc')->paginate(12);
                }
            }else{
                if($request->search){
                    $product_purchases = ProductPurchase::join('suppliers','product_purchases.supplier_id','suppliers.id')
                        //                    ->where('product_purchases.purchase_type','whole_purchase')
                        //                    ->where(function ($q) use ($request){
                        //                        $q->where('product_purchases.invoice_no','like','%'.$request->search.'%')
                        //                            ->orWhere('suppliers.name','like','%'.$request->search.'%');
                        //                    })
                        ->where('product_purchases.warehouse_id',$warehouse_id)
                        ->where('product_purchases.invoice_no','like','%'.$request->search.'%')
                        ->orWhere('suppliers.name','like','%'.$request->search.'%')
                        ->select(
                            'product_purchases.id',
                            'product_purchases.invoice_no',
                            'product_purchases.payment_type_id',
                            'product_purchases.sub_total_amount',
                            'product_purchases.discount_type',
                            'product_purchases.discount_percent',
                            'product_purchases.discount_amount',
                            'product_purchases.grand_total_amount',
                            'product_purchases.paid_amount',
                            'product_purchases.due_amount',
                            'product_purchases.purchase_date_time as date_time',
                            'product_purchases.user_id',
                            'product_purchases.supplier_id',
                            'product_purchases.warehouse_id'
                        )
                        ->latest('product_purchases.id','desc')->paginate(12);

                }else{
                    //$product_purchases = ProductPurchase::latest()->paginate(12);
                    $product_purchases = ProductPurchase::join('suppliers','product_purchases.supplier_id','suppliers.id')
                        ->where('product_purchases.warehouse_id',$warehouse_id)
                        ->select(
                            'product_purchases.id',
                            'product_purchases.invoice_no',
                            'product_purchases.payment_type_id',
                            'product_purchases.sub_total_amount',
                            'product_purchases.discount_type',
                            'product_purchases.discount_percent',
                            'product_purchases.discount_amount',
                            'product_purchases.grand_total_amount',
                            'product_purchases.paid_amount',
                            'product_purchases.due_amount',
                            'product_purchases.purchase_date_time as date_time',
                            'product_purchases.user_id',
                            'product_purchases.supplier_id',
                            'product_purchases.warehouse_id'
                        )
                        ->latest('product_purchases.id','desc')->paginate(12);
                }
            }

            if(count($product_purchases) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Purchase Found.',null);
                return response()->json($response,404);
            }else{
                return new ProductPurchaseCollection($product_purchases);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productPurchaseListPaginationWithSearchBySupplier(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required',
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
            $warehouse_id = $currentUserDetails['warehouse_id'];

            $supplier_id = $request->supplier_id;
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $search = $request->search;

            $product_purchases = ProductPurchase::join('suppliers','product_purchases.supplier_id','suppliers.id')
                ->select(
                    'product_purchases.id',
                    'product_purchases.invoice_no',
                    'product_purchases.supplier_invoice_no',
                    'product_purchases.payment_type_id',
                    'product_purchases.grand_total_amount',
                    'product_purchases.purchase_date_time as date_time',
                    'product_purchases.user_id',
                    'product_purchases.supplier_id',
                    'product_purchases.warehouse_id'
                );

            $product_purchases->where('product_purchases.supplier_id',$supplier_id)
                ->whereBetween('product_purchases.purchase_date', [$from_date, $to_date]);

            if($role !== 'Super Admin'){
                $product_purchases->where('product_purchases.warehouse_id',$warehouse_id);
            }

            if($search){
                $product_purchases->where('product_purchases.invoice_no','like','%'.$search.'%');
                $product_purchases->orWhere('suppliers.name','like','%'.$search.'%');
            }

            $product_purchase_data = $product_purchases->latest('product_purchases.id','desc')->paginate(12);

            $total_amount = $product_purchases->sum('product_purchases.grand_total_amount');

            if(count($product_purchase_data) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Purchase Report Found.',null);
                return response()->json($response,404);
            }else{
//                $result_data = [
//                    'purchase_data' => $product_purchase_data,
//                    'total_amount' => $total_amount
//                ];
//                return new SupplierPurchaseCollection($result_data);
                return response()->json(['success'=>true,'code' => 200,'data' => $product_purchase_data,'total_amount'=>$total_amount], 200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productPurchaseListPaginationWithSearchBySupplierPrint(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required',
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
            $warehouse_id = $currentUserDetails['warehouse_id'];

            $supplier_id = $request->supplier_id;
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $search = $request->search;

            $product_purchases = ProductPurchase::join('suppliers','product_purchases.supplier_id','suppliers.id')
                ->select(
                    'product_purchases.id',
                    'product_purchases.invoice_no',
                    'product_purchases.supplier_invoice_no',
                    'product_purchases.payment_type_id',
                    'product_purchases.grand_total_amount',
                    'product_purchases.purchase_date_time as date_time',
                    'product_purchases.user_id',
                    'product_purchases.supplier_id',
                    'product_purchases.warehouse_id'
                );

            $product_purchases->where('product_purchases.supplier_id',$supplier_id)
                ->whereBetween('product_purchases.purchase_date', [$from_date, $to_date]);

            if($role !== 'Super Admin'){
                $product_purchases->where('product_purchases.warehouse_id',$warehouse_id);
            }

            if($search){
                $product_purchases->where('product_purchases.invoice_no','like','%'.$search.'%');
                $product_purchases->orWhere('suppliers.name','like','%'.$search.'%');
            }

            $product_purchase_data = $product_purchases->latest('product_purchases.id','desc')->get();

            $total_amount = $product_purchases->sum('product_purchases.grand_total_amount');

            if(count($product_purchase_data) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Purchase Report Found.',null);
                return response()->json($response,404);
            }else{
//                $result_data = [
//                    'purchase_data' => $product_purchase_data,
//                    'total_amount' => $total_amount
//                ];
//                return new SupplierPurchaseCollection($result_data);
                return response()->json(['success'=>true,'code' => 200,'data' => $product_purchase_data,'total_amount'=>$total_amount], 200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productPurchaseDetails(Request $request){
        try {
            $product_purchase_details = DB::table('product_purchases')
                ->join('product_purchase_details','product_purchases.id','product_purchase_details.product_purchase_id')
                ->join('products','product_purchase_details.product_id','products.id')
                ->where('product_purchases.id',$request->product_purchase_id)
                ->select(
                    'product_purchases.warehouse_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_code',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id',
                    'product_purchase_details.qty',
                    'product_purchase_details.id as product_purchase_detail_id',
                    'product_purchase_details.purchase_price'
                )
                ->get();


            $product_purchase_detail_arr = [];
            if(count($product_purchase_details) > 0){
                foreach ($product_purchase_details as $product_purchase_detail){
                    $product = Product::find($product_purchase_detail->product_id);

                    $nested_data['product_id'] = $product_purchase_detail->product_id;
                    $nested_data['product_name'] = $product_purchase_detail->product_name;
                    $nested_data['product_code'] = $product_purchase_detail->product_code;
                    $nested_data['product_category_id'] = $product_purchase_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $product_purchase_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$product_purchase_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$product_purchase_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $product_purchase_detail->product_size_id;
                    $nested_data['product_size_name'] = $product_purchase_detail->product_size_id ? $product->size->name : '';
                    $nested_data['qty'] = $product_purchase_detail->qty;
                    $nested_data['product_purchase_detail_id'] = $product_purchase_detail->product_purchase_detail_id;
                    $nested_data['purchase_price'] = $product_purchase_detail->purchase_price;

                    array_push($product_purchase_detail_arr,$nested_data);
                }

                $response = APIHelpers::createAPIResponse(false,200,'',$product_purchase_detail_arr);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,404,'No Purchase Found.',null);
                return response()->json($response,404);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productPurchaseDetailsPrint(Request $request){
        try {
            $product_purchase_details = DB::table('product_purchases')
                ->join('product_purchase_details','product_purchases.id','product_purchase_details.product_purchase_id')
                ->join('products','product_purchase_details.product_id','products.id')
                ->where('product_purchases.id',$request->product_purchase_id)
                ->select(
                    'product_purchases.warehouse_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_code',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id',
                    'product_purchase_details.qty',
                    'product_purchase_details.id as product_purchase_detail_id',
                    'product_purchase_details.purchase_price'
                )
                ->get();

            $product_purchase_detail_arr = [];
            if(count($product_purchase_details) > 0){
                foreach ($product_purchase_details as $product_purchase_detail){
                    $product = Product::find($product_purchase_detail->product_id);

                    $nested_data['product_id'] = $product_purchase_detail->product_id;
                    $nested_data['product_name'] = $product_purchase_detail->product_name;
                    $nested_data['product_code'] = $product_purchase_detail->product_code;
                    $nested_data['product_category_id'] = $product_purchase_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $product_purchase_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$product_purchase_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$product_purchase_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $product_purchase_detail->product_size_id;
                    $nested_data['product_size_name'] = $product_purchase_detail->product_size_id ? $product->size->name : '';
                    $nested_data['qty'] = $product_purchase_detail->qty;
                    $nested_data['product_purchase_detail_id'] = $product_purchase_detail->product_purchase_detail_id;
                    $nested_data['purchase_price'] = $product_purchase_detail->purchase_price;

                    array_push($product_purchase_detail_arr,$nested_data);
                }

                $supplier_details = DB::table('product_purchases')
                    ->join('suppliers','product_purchases.supplier_id','suppliers.id')
                    ->join('warehouses','product_purchases.warehouse_id','warehouses.id')
                    ->where('product_purchases.id',$request->product_purchase_id)
                    ->select(
                        'suppliers.id as supplier_id',
                        'suppliers.name as supplier_name',
                        'suppliers.phone as supplier_phone',
                        'suppliers.address as supplier_address',
                        'warehouses.id as warehouse_id',
                        'warehouses.name as warehouse_name',
                        'warehouses.phone as warehouse_phone',
                        'warehouses.address as warehouse_address'
                    )
                    ->first();

                return response()->json(['success' => true,'code' => 200,'data' => $product_purchase_detail_arr, 'info' => $supplier_details], 200);
            }else{
                $response = APIHelpers::createAPIResponse(true,404,'No Purchase Found.',null);
                return response()->json($response,404);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
