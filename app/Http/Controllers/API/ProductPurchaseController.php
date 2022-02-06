<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductPurchaseCollection;
use App\PaymentPaid;
use App\Product;
use App\ProductPurchase;
use App\ProductPurchaseDetail;
use App\ProductPurchaseReturn;
use App\ProductPurchaseReturnDetail;
use App\ProductSale;
use App\Stock;
use App\Transaction;
use App\VoucherType;
use App\WarehouseCurrentStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductPurchaseController extends Controller
{
    public function productWholePurchaseCreate(Request $request){
        try {
            $this->validate($request, [
                'supplier_id'=> 'required',
                'warehouse_id'=> 'required',
                'paid_amount'=> 'required',
                'due_amount'=> 'required',
                'total_amount'=> 'required',
                'payment_type'=> 'required',
            ]);

            $get_invoice_no = ProductPurchase::latest()->pluck('invoice_no')->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("purchase-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1000;
            }
            $final_invoice = 'purchase-'.$invoice_no;

            $date = date('Y-m-d');
            $date_time = date('Y-m-d h:i:s');

            $user_id = Auth::user()->id;
            $supplier_id=$request->supplier_id;
            $warehouse_id = $request->warehouse_id;
            $store_id = NULL;

            // product purchase
            $productPurchase = new ProductPurchase();
            $productPurchase ->invoice_no = $final_invoice;
            $productPurchase ->user_id = $user_id;
            $productPurchase ->supplier_id = $supplier_id;
            $productPurchase ->warehouse_id = $warehouse_id;
            $productPurchase ->purchase_type = 'whole_purchase';
            $productPurchase ->sub_total = $request->sub_total;
            $productPurchase ->discount_type = $request->discount_type ? $request->discount_type : NULL;
            $productPurchase ->discount_percent = $request->discount_percent ? $request->discount_percent : 0;
            $productPurchase ->discount_amount = $request->discount_amount ? $request->discount_amount : 0;
            $productPurchase ->after_discount_amount = $request->after_discount_amount ? $request->after_discount_amount : 0;
            $productPurchase ->paid_amount = $request->paid_amount;
            $productPurchase ->due_amount = $request->due_amount;
            $productPurchase ->total_amount = $request->total_amount;
            $productPurchase ->purchase_date = $date;
            $productPurchase ->purchase_date_time = $date_time;
            $productPurchase->save();
            $insert_id = $productPurchase->id;

            if($insert_id)
            {
                foreach ($request->products as $data) {

                    $product_id =  $data['product_id'];

                    $barcode = Product::where('id',$product_id)->pluck('barcode')->first();

                    // product purchase detail
                    $purchase_purchase_detail = new ProductPurchaseDetail();
                    $purchase_purchase_detail->product_purchase_id = $insert_id;
                    $purchase_purchase_detail->product_id = $product_id;
                    $purchase_purchase_detail->qty = $data['qty'];
                    $purchase_purchase_detail->price = $data['price'];
                    $purchase_purchase_detail->mrp_price = $data['mrp_price'];
                    $purchase_purchase_detail->sub_total = $data['qty']*$data['price'];
                    $purchase_purchase_detail->barcode = $barcode;
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
                    $stock->stock_type = 'whole_purchase';
                    $stock->stock_where = 'warehouse';
                    $stock->stock_in_out = 'stock_in';
                    $stock->previous_stock = $previous_stock;
                    $stock->stock_in = $data['qty'];
                    $stock->stock_out = 0;
                    $stock->current_stock = $previous_stock + $data['qty'];
                    $stock->stock_date = $date;
                    $stock->stock_date_time = $date_time;
                    $stock->save();

                    // warehouse current stock
                    $check_exists_warehouse_current_stock = WarehouseCurrentStock::where('warehouse_id',$request->warehouse_id)
                        ->where('product_id',$product_id)
                        ->first();
                    if($check_exists_warehouse_current_stock){
                        $warehouse_current_stock_update = WarehouseCurrentStock::find($check_exists_warehouse_current_stock->id);
                        $warehouse_current_stock_update->current_stock=$check_exists_warehouse_current_stock->current_stock + $data['qty'];
                        $warehouse_current_stock_update->save();
                    }else{
                        $warehouse_current_stock = new WarehouseCurrentStock();
                        $warehouse_current_stock->warehouse_id=$request->warehouse_id;
                        $warehouse_current_stock->product_id=$product_id;
                        $warehouse_current_stock->current_stock=$data['qty'];
                        $warehouse_current_stock->save();
                    }
                }

                // transaction
//                $transaction = new Transaction();
//                $transaction->ref_id = $insert_id;
//                $transaction->invoice_no = $final_invoice;
//                $transaction->user_id = $user_id;
//                $transaction->warehouse_id = $request->warehouse_id;
//                $transaction->party_id = $request->party_id;
//                $transaction->transaction_type = 'whole_purchase';
//                $transaction->payment_type = $request->payment_type;
//                $transaction->amount = $request->paid_amount;
//                $transaction->transaction_date = $date;
//                $transaction->transaction_date_time = $date_time;
//                $transaction->save();

                // payment paid
//                $payment_paid = new PaymentPaid();
//                $payment_paid->invoice_no = $final_invoice;
//                $payment_paid->product_purchase_id = $insert_id;
//                $payment_paid->user_id = $user_id;
//                $payment_paid->party_id = $request->party_id;
//                $payment_paid->paid_type = 'Purchase';
//                $payment_paid->paid_amount = $request->paid_amount;
//                $payment_paid->due_amount = $request->due_amount;
//                $payment_paid->current_paid_amount = $request->paid_amount;
//                $payment_paid->paid_date = $date;
//                $payment_paid->paid_date_time = $date_time;
//                $payment_paid->save();

                // posting
                $month = date('m', strtotime($date));
                $year = date('Y', strtotime($date));
                $transaction_date_time = date('Y-m-d H:i:s');

                $get_voucher_name = VoucherType::where('id',7)->pluck('name')->first();
                $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',7)->latest()->pluck('voucher_no')->first();
                if(!empty($get_voucher_no)){
                    $get_voucher_name_str = $get_voucher_name."-";
                    $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                    $voucher_no = $get_voucher+1;
                }else{
                    $voucher_no = 8000;
                }
                $final_voucher_no = $get_voucher_name.'-'.$voucher_no;
                $chart_of_account_transactions = new ChartOfAccountTransaction();
                $chart_of_account_transactions->warehouse_id = $warehouse_id;
                $chart_of_account_transactions->store_id = $store_id;
                $chart_of_account_transactions->ref_id = $insert_id;
                $chart_of_account_transactions->transaction_type = 'Purchases';
                $chart_of_account_transactions->user_id = $user_id;
                $chart_of_account_transactions->voucher_type_id = 7;
                $chart_of_account_transactions->voucher_no = $final_voucher_no;
                $chart_of_account_transactions->is_approved = 'approved';
                $chart_of_account_transactions->transaction_date = $date;
                $chart_of_account_transactions->transaction_date_time = $transaction_date_time;
                $chart_of_account_transactions->save();
                $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;

                if($chart_of_account_transactions_insert_id){

                    // Purchase
//                    $sales_chart_of_account_info = ChartOfAccount::where('head_name','Purchases')->first();
//                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                    $chart_of_account_transaction_details->store_id = $store_id;
//                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                    $chart_of_account_transaction_details->chart_of_account_id = $sales_chart_of_account_info->id;
//                    $chart_of_account_transaction_details->chart_of_account_number = $sales_chart_of_account_info->head_code;
//                    $chart_of_account_transaction_details->chart_of_account_name = 'Purchases';
//                    $chart_of_account_transaction_details->chart_of_account_parent_name = $sales_chart_of_account_info->parent_head_name;
//                    $chart_of_account_transaction_details->chart_of_account_type = $sales_chart_of_account_info->head_type;
//                    $chart_of_account_transaction_details->debit = $request->total_amount;
//                    $chart_of_account_transaction_details->credit = NULL;
//                    $chart_of_account_transaction_details->description = 'Expense For Purchases';
//                    $chart_of_account_transaction_details->year = $year;
//                    $chart_of_account_transaction_details->month = $month;
//                    $chart_of_account_transaction_details->transaction_date = $date;
//                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
//                    $chart_of_account_transaction_details->save();

                    // cash
                    if($request->payment_type == 'Cash'){
                        $cash_chart_of_account_info = ChartOfAccount::where('head_name','Cash')->first();
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                        $chart_of_account_transaction_details->store_id = $store_id;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = 'Cash';
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
                        $chart_of_account_transaction_details->debit = NULL;
                        $chart_of_account_transaction_details->credit = $request->total_amount;
                        $chart_of_account_transaction_details->description = 'Cash Out For Purchases';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                        $chart_of_account_transaction_details->save();
                    }else{

                    }
                }


                $response = APIHelpers::createAPIResponse(false,201,'Product Purchase Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Purchase Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
