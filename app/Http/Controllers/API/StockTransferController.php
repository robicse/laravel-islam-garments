<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;


use App\Product;
use App\Stock;
use App\StockTransfer;
use App\StockTransferDetail;
use App\WarehouseCurrentStock;
use App\WarehouseStoreCurrentStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockTransferController extends Controller
{
    public function warehouseToStoreStockCreate(Request $request){
        try {
            // required and unique
            $validator = Validator::make($request->all(), [
                'warehouse_id'=> 'required',
                'store_id'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $date = date('Y-m-d');
            $date_time = date('Y-m-d h:i:s');

            $user_id = Auth::user()->id;
            $warehouse_id = $request->warehouse_id;
            $store_id = $request->store_id;
            $miscellaneous_comment = $request->miscellaneous_comment;
            $miscellaneous_charge = $request->miscellaneous_charge ? $request->miscellaneous_charge : 0;


            $get_invoice_no = StockTransfer::latest()->pluck('invoice_no')->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("STN-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1000;
            }

            $sub_total = 0;
            $total_amount = 0;
            //$total_vat_amount = 0;
            foreach ($request->products as $data) {
                $product_id = $data['product_id'];
                //$price = Product::where('id',$product_id)->pluck('purchase_price')->first();
                $Product_info = Product::where('id',$product_id)->first();
                //$total_vat_amount += ($data['qty']*$Product_info->vat_amount);
                //$total_amount += ($data['qty']*$Product_info->vat_amount) + ($data['qty']*$Product_info->purchase_price);
                $sub_total += $data['qty']*$Product_info->purchase_price;
                $total_amount += $data['qty']*$Product_info->purchase_price;
            }

            $total_amount += $miscellaneous_charge;

            $final_invoice = 'STN-'.$invoice_no;
            $stock_transfer = new StockTransfer();
            $stock_transfer->invoice_no=$final_invoice;
            $stock_transfer->user_id=Auth::user()->id;
            $stock_transfer->warehouse_id = $warehouse_id;
            $stock_transfer->store_id = $store_id;
            $stock_transfer->sub_total = $sub_total;
            $stock_transfer->total_vat_amount = 0;
            $stock_transfer->miscellaneous_comment = $miscellaneous_comment;
            $stock_transfer->miscellaneous_charge = $miscellaneous_charge;
            $stock_transfer->total_amount = $total_amount;
            $stock_transfer->paid_amount = 0;
            $stock_transfer->due_amount = $total_amount;
            $stock_transfer->issue_date = $date;
            $stock_transfer->due_date = $date;
            $stock_transfer->save();
            $stock_transfer_insert_id = $stock_transfer->id;

            $insert_id = false;

            foreach ($request->products as $data) {

                $product_id = $data['product_id'];
                $product_info = Product::where('id',$product_id)->first();


                $stock_transfer_detail = new StockTransferDetail();
                $stock_transfer_detail->stock_transfer_id = $stock_transfer_insert_id;
                $stock_transfer_detail->product_unit_id = $data['product_unit_id'];
                $stock_transfer_detail->product_brand_id = $data['product_brand_id'] ? $data['product_brand_id'] : NULL;
                $stock_transfer_detail->product_id = $product_id;
                $stock_transfer_detail->barcode = $product_info->barcode;
                $stock_transfer_detail->qty = $data['qty'];
                //$stock_transfer_detail->vat_amount = $data['qty']*$product_info->vat_percentage;
                $stock_transfer_detail->vat_amount = 0;
                $stock_transfer_detail->price = $product_info->purchase_price;
                //$stock_transfer_detail->sub_total = ($data['qty']*$product_info->vat_percentage) + ($data['qty']*$product_info->purchase_price);
                $stock_transfer_detail->sub_total = $data['qty']*$product_info->purchase_price;
                $stock_transfer_detail->issue_date = $date;
                $stock_transfer_detail->save();


                $check_previous_warehouse_current_stock = Stock::where('warehouse_id',$warehouse_id)
                    ->where('product_id',$product_id)
                    ->where('stock_where','warehouse')
                    ->latest('id','desc')
                    ->pluck('current_stock')
                    ->first();

                if($check_previous_warehouse_current_stock){
                    $previous_warehouse_current_stock = $check_previous_warehouse_current_stock;
                }else{
                    $previous_warehouse_current_stock = 0;
                }

                // stock out warehouse product
                $stock = new Stock();
                $stock->ref_id = $stock_transfer_insert_id;
                $stock->user_id = $user_id;
                $stock->warehouse_id = $warehouse_id;
                $stock->store_id = NULL;
                $stock->product_id = $product_id;
                $stock->product_unit_id = $data['product_unit_id'];
                $stock->product_brand_id = $data['product_brand_id'] ? $data['product_brand_id'] : NULL;
                $stock->stock_type = 'from_warehouse_to_store';
                $stock->stock_where = 'warehouse';
                $stock->stock_in_out = 'stock_out';
                $stock->previous_stock = $previous_warehouse_current_stock;
                $stock->stock_in = 0;
                $stock->stock_out = $data['qty'];
                $stock->current_stock = $previous_warehouse_current_stock - $data['qty'];
                $stock->stock_date = $date;
                $stock->stock_date_time = $date_time;
                $stock->save();


                $check_previous_store_current_stock = Stock::where('warehouse_id',$warehouse_id)
                    ->where('store_id',$store_id)
                    ->where('product_id',$product_id)
                    ->where('stock_where','store')
                    ->latest('id','desc')
                    ->pluck('current_stock')
                    ->first();

                if($check_previous_store_current_stock){
                    $previous_store_current_stock = $check_previous_store_current_stock;
                }else{
                    $previous_store_current_stock = 0;
                }

                // stock in store product
                $stock = new Stock();
                $stock->ref_id = $stock_transfer_insert_id;
                $stock->user_id = $user_id;
                $stock->warehouse_id = $warehouse_id;
                $stock->store_id = $store_id;
                $stock->product_id = $product_id;
                $stock->product_unit_id = $data['product_unit_id'];
                $stock->product_brand_id = $data['product_brand_id'] ? $data['product_brand_id'] : NULL;
                $stock->stock_type = 'from_warehouse_to_store';
                $stock->stock_where = 'store';
                $stock->stock_in_out = 'stock_in';
                $stock->previous_stock = $previous_store_current_stock;
                $stock->stock_in = $data['qty'];
                $stock->stock_out = 0;
                $stock->current_stock = $previous_store_current_stock + $data['qty'];
                $stock->stock_date = $date;
                $stock->stock_date_time = $date_time;
                $stock->save();
                $insert_id = $stock->id;

                // warehouse current stock
                $warehouse_current_stock_update = WarehouseCurrentStock::where('warehouse_id',$request->warehouse_id)
                    ->where('product_id',$product_id)
                    ->first();
                $exists_current_stock = $warehouse_current_stock_update->current_stock;
                $final_warehouse_current_stock = $exists_current_stock - $data['qty'];
                $warehouse_current_stock_update->current_stock=$final_warehouse_current_stock;
                $warehouse_current_stock_update->save();

                // warehouse store current stock
                $check_exists_warehouse_store_current_stock = WarehouseStoreCurrentStock::where('warehouse_id',$warehouse_id)
                    ->where('store_id',$store_id)
                    ->where('product_id',$product_id)
                    ->first();

                if($check_exists_warehouse_store_current_stock){
                    $exists_current_stock = $check_exists_warehouse_store_current_stock->current_stock;
                    $final_warehouse_current_stock = $exists_current_stock + $data['qty'];
                    $check_exists_warehouse_store_current_stock->current_stock=$final_warehouse_current_stock;
                    $check_exists_warehouse_store_current_stock->save();
                }else{
                    $warehouse_store_current_stock = new WarehouseStoreCurrentStock();
                    $warehouse_store_current_stock->warehouse_id=$warehouse_id;
                    $warehouse_store_current_stock->store_id=$store_id;
                    $warehouse_store_current_stock->product_id=$product_id;
                    $warehouse_store_current_stock->current_stock=$data['qty'];
                    $warehouse_store_current_stock->save();
                }
            }

            // transaction
            //        $transaction = new Transaction();
            //        $transaction->ref_id = $stock_transfer_insert_id;
            //        $transaction->invoice_no = $final_invoice;
            //        $transaction->user_id = $user_id;
            //        $transaction->warehouse_id = $request->warehouse_id;
            //        $transaction->party_id = $request->party_id;
            //        $transaction->transaction_type = '';
            //        $transaction->payment_type = 'Cash';
            //        $transaction->amount = $request->total_amount;
            //        $transaction->transaction_date = $date;
            //        $transaction->transaction_date_time = $date_time;
            //        $transaction->save();

            $response = APIHelpers::createAPIResponse(false,201,'Warehouse To Store Stock Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
