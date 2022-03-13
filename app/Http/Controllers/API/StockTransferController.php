<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductStockTransferCollection;
use App\Product;
use App\Stock;
use App\StockTransfer;
use App\StockTransferDetail;
use App\Supplier;
use App\VoucherType;
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
//        try {
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
            //$payment_type_id = $request->payment_type_id;
            $payment_type_id = NULL;
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
            $paid_amount = $request->paid_amount;
            $due_amount = $request->due_amount;
            $products = json_decode($request->products);
            foreach ($products as $data) {
                $product_id = $data->id;
                $qty = $data->qty;
                $Product_info = Product::where('id',$product_id)->first();
                $sub_total += $qty*$Product_info->purchase_price;
                $total_amount += $qty*$Product_info->purchase_price;
            }

            $total_amount += $miscellaneous_charge;

            $final_invoice = 'STN-'.$invoice_no;
            $stock_transfer = new StockTransfer();
            $stock_transfer->invoice_no=$final_invoice;
            $stock_transfer->user_id=Auth::user()->id;
            $stock_transfer->payment_type_id = $payment_type_id;
            $stock_transfer->warehouse_id = $warehouse_id;
            $stock_transfer->store_id = $store_id;
            $stock_transfer->sub_total_amount = $sub_total;
            $stock_transfer->total_vat_amount = 0;
            $stock_transfer->miscellaneous_comment = $miscellaneous_comment;
            $stock_transfer->miscellaneous_charge = $miscellaneous_charge;
            $stock_transfer->grand_total_amount = $total_amount;
            $stock_transfer->paid_amount = $paid_amount;
            $stock_transfer->due_amount = $due_amount;
            $stock_transfer->issue_date = $date;
            $stock_transfer->due_date = $date;
            $stock_transfer->save();
            $stock_transfer_insert_id = $stock_transfer->id;

            foreach ($products as $data) {
                $product_id = $data->id;
                $qty = $data->qty;
                $product_info = Product::where('id',$product_id)->first();

                $stock_transfer_detail = new StockTransferDetail();
                $stock_transfer_detail->stock_transfer_id = $stock_transfer_insert_id;
                $stock_transfer_detail->product_id = $product_id;
                $stock_transfer_detail->barcode = $product_info->barcode;
                $stock_transfer_detail->qty = $qty;
                $stock_transfer_detail->vat_amount = 0;
                $stock_transfer_detail->price = $product_info->purchase_price;
                $stock_transfer_detail->sub_total = $qty*$product_info->purchase_price;
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
                $stock->stock_type = 'Stock Transfer From Warehouse';
                $stock->stock_where = 'warehouse';
                $stock->stock_in_out = 'stock_out';
                $stock->previous_stock = $previous_warehouse_current_stock;
                $stock->stock_in = 0;
                $stock->stock_out = $qty;
                $stock->current_stock = $previous_warehouse_current_stock - $qty;
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
                $stock->stock_type = 'Stock Transfer To Store';
                $stock->stock_where = 'store';
                $stock->stock_in_out = 'stock_in';
                $stock->previous_stock = $previous_store_current_stock;
                $stock->stock_in = $qty;
                $stock->stock_out = 0;
                $stock->current_stock = $previous_store_current_stock + $qty;
                $stock->stock_date = $date;
                $stock->stock_date_time = $date_time;
                $stock->save();

                // warehouse current stock
                $warehouse_current_stock_update = WarehouseCurrentStock::where('warehouse_id',$warehouse_id)
                    ->where('product_id',$product_id)
                    ->first();
                if(!empty($warehouse_current_stock_update)){
                    $exists_current_stock = $warehouse_current_stock_update->current_stock;
                    $final_warehouse_current_stock = $exists_current_stock - $qty;
                    $warehouse_current_stock_update->current_stock=$final_warehouse_current_stock;
                    $warehouse_current_stock_update->save();
                }else{
                    $warehouse_store_current_stock = new WarehouseCurrentStock();
                    $warehouse_store_current_stock->warehouse_id=$warehouse_id;
                    $warehouse_store_current_stock->product_id=$product_id;
                    $warehouse_store_current_stock->current_stock=$qty;
                    $warehouse_store_current_stock->save();
                }


                // warehouse store current stock
                $check_exists_warehouse_store_current_stock = WarehouseStoreCurrentStock::where('warehouse_id',$warehouse_id)
                    ->where('store_id',$store_id)
                    ->where('product_id',$product_id)
                    ->first();

                if($check_exists_warehouse_store_current_stock){
                    $exists_current_stock = $check_exists_warehouse_store_current_stock->current_stock;
                    $final_warehouse_current_stock = $exists_current_stock + $qty;
                    $check_exists_warehouse_store_current_stock->current_stock=$final_warehouse_current_stock;
                    $check_exists_warehouse_store_current_stock->save();
                }else{
                    $warehouse_store_current_stock = new WarehouseStoreCurrentStock();
                    $warehouse_store_current_stock->warehouse_id=$warehouse_id;
                    $warehouse_store_current_stock->store_id=$store_id;
                    $warehouse_store_current_stock->product_id=$product_id;
                    $warehouse_store_current_stock->current_stock=$qty;
                    $warehouse_store_current_stock->save();
                }
            }

            // posting
            $month = date('m', strtotime($date));
            $year = date('Y', strtotime($date));
            $transaction_date_time = date('Y-m-d H:i:s');

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

            // Inventory Account Info
            $inventory_chart_of_account_info = ChartOfAccount::where('head_name', 'Inventory')->first();

            // Cash In Hand Account Info
            $cash_chart_of_account_info = ChartOfAccount::where('head_name', 'Cash In Hand')->first();

            // Cash In Hand Debit
//            $description = $cash_chart_of_account_info->head_name.' Store Debited For Stock IN';
//            chartOfAccountTransactionDetails($stock_transfer_insert_id, $final_invoice, $user_id, 9, $final_voucher_no, 'Stock Transfer To Store', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, $total_amount, NULL, $description, 'Approved');

            // Cash In Hand Credit
//            $description = $cash_chart_of_account_info->head_name.' Warehouse Credited For Stock OUT';
//            chartOfAccountTransactionDetails($stock_transfer_insert_id, $final_invoice, $user_id, 9, $final_voucher_no, 'Stock Transfer From Warehouse', $date, $transaction_date_time, $year, $month, $warehouse_id, NULL, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, NULL, $total_amount, $description, 'Approved');


            // Inventory Debit
            $description = $inventory_chart_of_account_info->head_name.' Store Inventory Debited For Stock IN';
            chartOfAccountTransactionDetails($stock_transfer_insert_id, $final_invoice, $user_id, 9, $final_voucher_no, 'Stock Transfer To Store', $date, $transaction_date_time, $year, $month, NULL, $store_id, $payment_type_id, NULL, NULL, NULL, $inventory_chart_of_account_info->id, $inventory_chart_of_account_info->head_code, $inventory_chart_of_account_info->head_name, $inventory_chart_of_account_info->parent_head_name, $inventory_chart_of_account_info->head_type, $total_amount, NULL, $description, 'Approved');

            //Inventory Credit
            $description = $inventory_chart_of_account_info->head_name.' Warehouse Inventory Credited For Stock OUT';
            chartOfAccountTransactionDetails($stock_transfer_insert_id, $final_invoice, $user_id, 9, $final_voucher_no, 'Stock Transfer From Warehouse', $date, $transaction_date_time, $year, $month, $warehouse_id, NULL, $payment_type_id, NULL, NULL, NULL, $inventory_chart_of_account_info->id, $inventory_chart_of_account_info->head_code, $inventory_chart_of_account_info->head_name, $inventory_chart_of_account_info->parent_head_name, $inventory_chart_of_account_info->head_type, NULL, $total_amount, $description, 'Approved');

            $response = APIHelpers::createAPIResponse(false,201,'Warehouse To Store Stock Added Successfully.',null);
            return response()->json($response,201);
//        } catch (\Exception $e) {
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }

    public function stockTransferListWithSearch(Request $request){
        try {
            $user_id = Auth::user()->id;
            $currentUserDetails = currentUserDetails($user_id);
            $role = $currentUserDetails['role'];
            $warehouse_id = $currentUserDetails['warehouse_id'];
            $store_id = $currentUserDetails['store_id'];

            if($role == 'Super Admin') {
                if ($request->search) {
                    $stock_transfer_lists = StockTransfer::leftJoin('users', 'stock_transfers.user_id', 'users.id')
                        ->leftJoin('warehouses', 'stock_transfers.warehouse_id', 'warehouses.id')
                        ->leftJoin('stores', 'stock_transfers.store_id', 'stores.id')
                        ->where('stock_transfers.invoice_no', 'like', '%' . $request->search . '%')
                        ->orWhere('warehouses.name', 'like', '%' . $request->search . '%')
                        ->orWhere('stores.name', 'like', '%' . $request->search . '%')
                        ->select(
                            'stock_transfers.id',
                            'stock_transfers.invoice_no',
                            'stock_transfers.payment_type_id',
                            'stock_transfers.sub_total_amount',
                            'stock_transfers.grand_total_amount',
                            'stock_transfers.paid_amount',
                            'stock_transfers.due_amount',
                            'stock_transfers.issue_date',
                            'stock_transfers.created_at as date_time',
                            'stock_transfers.miscellaneous_comment',
                            'stock_transfers.miscellaneous_charge',
                            'stock_transfers.total_vat_amount',
                            'stock_transfers.user_id',
                            'stock_transfers.warehouse_id',
                            'stock_transfers.store_id'
                        )
                        ->orderBy('stock_transfers.id', 'desc')
                        ->paginate(12);
                } else {
                    $stock_transfer_lists = StockTransfer::leftJoin('users', 'stock_transfers.user_id', 'users.id')
                        ->leftJoin('warehouses', 'stock_transfers.warehouse_id', 'warehouses.id')
                        ->leftJoin('stores', 'stock_transfers.store_id', 'stores.id')
                        ->select(
                            'stock_transfers.id',
                            'stock_transfers.invoice_no',
                            'stock_transfers.payment_type_id',
                            'stock_transfers.sub_total_amount',
                            'stock_transfers.grand_total_amount',
                            'stock_transfers.grand_total_amount',
                            'stock_transfers.paid_amount',
                            'stock_transfers.due_amount',
                            'stock_transfers.issue_date',
                            'stock_transfers.created_at as date_time',
                            'stock_transfers.miscellaneous_comment',
                            'stock_transfers.miscellaneous_charge',
                            'stock_transfers.total_vat_amount',
                            'stock_transfers.user_id',
                            'stock_transfers.warehouse_id',
                            'stock_transfers.store_id'
                        )
                        ->orderBy('stock_transfers.id', 'desc')
                        ->paginate(12);
                }
            }else{
                if ($request->search) {
                    $stock_transfer_lists = StockTransfer::leftJoin('users', 'stock_transfers.user_id', 'users.id')
                        ->leftJoin('warehouses', 'stock_transfers.warehouse_id', 'warehouses.id')
                        ->leftJoin('stores', 'stock_transfers.store_id', 'stores.id')
                        ->where('stock_transfers.warehouse_id',$warehouse_id)
                        ->where('stock_transfers.store_id',$store_id)
                        ->where('stock_transfers.invoice_no', 'like', '%' . $request->search . '%')
                        ->orWhere('warehouses.name', 'like', '%' . $request->search . '%')
                        ->orWhere('stores.name', 'like', '%' . $request->search . '%')
                        ->select(
                            'stock_transfers.id',
                            'stock_transfers.invoice_no',
                            'stock_transfers.payment_type_id',
                            'stock_transfers.sub_total_amount',
                            'stock_transfers.grand_total_amount',
                            'stock_transfers.paid_amount',
                            'stock_transfers.due_amount',
                            'stock_transfers.issue_date',
                            'stock_transfers.created_at as date_time',
                            'stock_transfers.miscellaneous_comment',
                            'stock_transfers.miscellaneous_charge',
                            'stock_transfers.total_vat_amount',
                            'stock_transfers.user_id',
                            'stock_transfers.warehouse_id',
                            'stock_transfers.store_id'
                        )
                        ->orderBy('stock_transfers.id', 'desc')
                        ->paginate(12);
                } else {
                    $stock_transfer_lists = StockTransfer::leftJoin('users', 'stock_transfers.user_id', 'users.id')
                        ->leftJoin('warehouses', 'stock_transfers.warehouse_id', 'warehouses.id')
                        ->leftJoin('stores', 'stock_transfers.store_id', 'stores.id')
                        ->where('stock_transfers.warehouse_id',$warehouse_id)
                        ->where('stock_transfers.store_id',$store_id)
                        ->select(
                            'stock_transfers.id',
                            'stock_transfers.invoice_no',
                            'stock_transfers.payment_type_id',
                            'stock_transfers.sub_total_amount',
                            'stock_transfers.grand_total_amount',
                            'stock_transfers.grand_total_amount',
                            'stock_transfers.paid_amount',
                            'stock_transfers.due_amount',
                            'stock_transfers.issue_date',
                            'stock_transfers.created_at as date_time',
                            'stock_transfers.miscellaneous_comment',
                            'stock_transfers.miscellaneous_charge',
                            'stock_transfers.total_vat_amount',
                            'stock_transfers.user_id',
                            'stock_transfers.warehouse_id',
                            'stock_transfers.store_id'
                        )
                        ->orderBy('stock_transfers.id', 'desc')
                        ->paginate(12);
                }
            }

            if($stock_transfer_lists === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Stock Transfer Found.',null);
                return response()->json($response,404);
            }else{
                //$response = APIHelpers::createAPIResponse(false,200,'',$stock_transfer_lists);
                //return response()->json($response,200);
                return new ProductStockTransferCollection($stock_transfer_lists);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function stockTransferDetails(Request $request){
        try {
            $stock_transfer_details = DB::table('stock_transfers')
                ->join('stock_transfer_details','stock_transfers.id','stock_transfer_details.stock_transfer_id')
                ->join('products','stock_transfer_details.product_id','products.id')
                ->where('stock_transfers.id',$request->stock_transfer_id)
                ->select(
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_code',
                    'stock_transfers.warehouse_id',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id',
                    'stock_transfer_details.qty',
                    'stock_transfer_details.id as stock_transfer_detail_id',
                    'stock_transfer_details.price',
                    'stock_transfer_details.sub_total',
                    'stock_transfer_details.vat_amount'
                )
                ->get();

            $stock_transfer_arr = [];
            if(count($stock_transfer_details))
            {
                foreach($stock_transfer_details as $stock_transfer_detail){
                    $current_qty = warehouseProductCurrentStock($stock_transfer_detail->warehouse_id,$stock_transfer_detail->product_id);
                    $product = Product::find($stock_transfer_detail->product_id);

                    $nested_data['product_id']=$stock_transfer_detail->product_id;
                    $nested_data['product_name']=$stock_transfer_detail->product_name;
                    $nested_data['product_code'] = $stock_transfer_detail->product_code;
                    $nested_data['qty']=$stock_transfer_detail->qty;
                    $nested_data['product_category_id'] = $stock_transfer_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $stock_transfer_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$stock_transfer_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$stock_transfer_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $stock_transfer_detail->product_size_id;
                    $nested_data['product_size_name'] = $stock_transfer_detail->product_size_id ? $product->size->name : '';
                    $nested_data['stock_transfer_detail_id']=$stock_transfer_detail->stock_transfer_detail_id;
                    $nested_data['purchase_price']=$stock_transfer_detail->price;
                    $nested_data['sub_total']=$stock_transfer_detail->sub_total;
                    $nested_data['vat_amount']=$stock_transfer_detail->vat_amount;
                    $nested_data['current_qty']=$current_qty;

                    array_push($stock_transfer_arr, $nested_data);
                }
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$stock_transfer_arr);
            return response()->json($response,200);
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function stockTransferDetailsPrint(Request $request){
        try {
            $stock_transfer_details = DB::table('stock_transfers')
                ->join('stock_transfer_details','stock_transfers.id','stock_transfer_details.stock_transfer_id')
                ->join('products','stock_transfer_details.product_id','products.id')
                ->where('stock_transfers.id',$request->stock_transfer_id)
                ->select(
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_code',
                    'stock_transfers.warehouse_id',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id',
                    'stock_transfer_details.qty',
                    'stock_transfer_details.id as stock_transfer_detail_id',
                    'stock_transfer_details.price',
                    'stock_transfer_details.sub_total',
                    'stock_transfer_details.vat_amount'
                )
                ->get();

            $stock_transfer_arr = [];
            if(count($stock_transfer_details))
            {
                foreach($stock_transfer_details as $stock_transfer_detail){
                    $current_qty = warehouseProductCurrentStock($stock_transfer_detail->warehouse_id,$stock_transfer_detail->product_id);
                    $product = Product::find($stock_transfer_detail->product_id);

                    $nested_data['product_id']=$stock_transfer_detail->product_id;
                    $nested_data['product_name']=$stock_transfer_detail->product_name;
                    $nested_data['product_code'] = $stock_transfer_detail->product_code;
                    $nested_data['qty']=$stock_transfer_detail->qty;
                    $nested_data['product_category_id'] = $stock_transfer_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $stock_transfer_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$stock_transfer_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$stock_transfer_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $stock_transfer_detail->product_size_id;
                    $nested_data['product_size_name'] = $stock_transfer_detail->product_size_id ? $product->size->name : '';
                    $nested_data['stock_transfer_detail_id']=$stock_transfer_detail->stock_transfer_detail_id;
                    $nested_data['purchase_price']=$stock_transfer_detail->price;
                    $nested_data['sub_total']=$stock_transfer_detail->sub_total;
                    $nested_data['vat_amount']=$stock_transfer_detail->vat_amount;
                    $nested_data['current_qty']=$current_qty;

                    array_push($stock_transfer_arr, $nested_data);
                }
            }

            $info = DB::table('stock_transfers')
                ->join('warehouses','stock_transfers.warehouse_id','warehouses.id')
                ->join('stores','stock_transfers.store_id','stores.id')
                ->where('stock_transfers.id',$request->stock_transfer_id)
                ->select(
                    'warehouses.id as warehouse_id',
                    'warehouses.name as warehouse_name',
                    'warehouses.phone as warehouse_phone',
                    'warehouses.address as warehouse_address',
                    'store.id as stores_id',
                    'store.name as stores_name',
                    'store.phone as stores_phone',
                    'store.address as stores_address'
                )
                ->first();

            return response()->json(['success' => true,'code' => 200,'data' => $stock_transfer_arr, 'info' => $info], 200);
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }


    public function productSearchForStockTransferByWarehouseId(Request $request){
//        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required',
                'product_category_id'=> 'required',
                'product_unit_id'=> 'required',
                'warehouse_id'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }



            $product_info = productSearchForStockTransferByWarehouseId($request->warehouse_id,$request->type,$request->product_category_id,$request->product_size_id,$request->product_unit_id,$request->product_sub_unit_id,$request->product_code);

            if(empty($product_info)){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Product Found.',null);
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
