<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Product;
use App\Stock;
use App\StockTransfer;
use App\StockTransferDetail;
use App\StockTransferRequest;
use App\StockTransferRequestDetail;
use App\User;
use App\WarehouseCurrentStock;
use App\WarehouseStoreCurrentStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockTransferRequestController extends Controller
{

    public function storeToWarehouseStockRequestCreate(Request $request){
        try {
            // required and unique
            $validator = Validator::make($request->all(), [
                'request_from_store_id'=> 'required',
                'request_to_warehouse_id'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $date = date('Y-m-d');

            $user_id = Auth::user()->id;
            $request_to_warehouse_id = $request->request_to_warehouse_id;
            $request_from_store_id = $request->request_from_store_id;


            // discount start
            $sum_total_amount = 0;
            $products = json_decode($request->products);
            foreach ($products as $data) {
                $price = $data->purchase_price;
                $qty = $data->qty;
                $sum_total_amount += (float)$price * (float)$qty;
            }
            // discount start


            $get_invoice_no = StockTransferRequest::latest()->pluck('invoice_no')->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("STRN-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 200200;
            }

            $final_invoice = 'STRN-'.$invoice_no;
            $stock_transfer_request = new StockTransferRequest();
            $stock_transfer_request->invoice_no=$final_invoice;
            $stock_transfer_request->request_to_warehouse_id = $request_to_warehouse_id;
            $stock_transfer_request->request_from_store_id = $request_from_store_id;
            $stock_transfer_request->request_by_user_id=$user_id;
            $stock_transfer_request->request_date=$date;
            $stock_transfer_request->request_remarks=$request->request_remarks;
            $stock_transfer_request->request_status='Pending';
            $stock_transfer_request->sub_total_amount=$sum_total_amount;
            $stock_transfer_request->grand_total_amount=$sum_total_amount;
            $stock_transfer_request->save();
            $stock_transfer_request_insert_id = $stock_transfer_request->id;

            $products = json_decode($request->products);
            foreach ($products as $data) {

                $product_id = $data->id;
                $qty = $data->qty;
                $purchase_price = $data->purchase_price;

                $product_info = Product::where('id',$product_id)->first();

                $stock_transfer_request_detail = new StockTransferRequestDetail();
                $stock_transfer_request_detail->stock_transfer_request_id = $stock_transfer_request_insert_id;
                $stock_transfer_request_detail->product_id = $product_id;
                $stock_transfer_request_detail->barcode = $product_info->barcode;
                $stock_transfer_request_detail->request_qty = $qty;
                $stock_transfer_request_detail->send_qty = 0;
                $stock_transfer_request_detail->received_qty = 0;
                $stock_transfer_request_detail->price = $purchase_price;
                $stock_transfer_request_detail->vat_amount = 0;
                $stock_transfer_request_detail->sub_total = $qty*$purchase_price;
                $stock_transfer_request_detail->received_date = $date;
                $stock_transfer_request_detail->save();
            }

            $response = APIHelpers::createAPIResponse(false,201,'Stock Transfer Request Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeToWarehouseStockRequestListPaginationWithSearch(Request $request){
        try {
            if($request->search){
                $stock_transfer_request_lists = DB::table('stock_transfer_requests')
                    ->leftJoin('users','stock_transfer_requests.request_by_user_id','users.id')
                    ->leftJoin('warehouses','stock_transfer_requests.request_to_warehouse_id','warehouses.id')
                    ->leftJoin('stores','stock_transfer_requests.request_from_store_id','stores.id')
                    ->where('stock_transfer_requests.invoice_no','like','%'.$request->search.'%')
                    ->orWhere('warehouses.name','like','%'.$request->search.'%')
                    ->orWhere('stores.name','like','%'.$request->search.'%')
                    ->select('stock_transfer_requests.id','stock_transfer_requests.invoice_no','stock_transfer_requests.sub_total_amount','stock_transfer_requests.grand_total_amount','stock_transfer_requests.request_date as date','stock_transfer_requests.created_at as date_time','users.name as user_name','warehouses.id as warehouse_id','warehouses.name as warehouse_name','stores.id as store_id','stores.name as store_name','stores.phone as store_phone','stores.email as store_email','stores.address as store_address')
                    ->orderBy('stock_transfer_requests.id','desc')
                    ->paginate(12);
            }else{
                $stock_transfer_request_lists = DB::table('stock_transfer_requests')
                    ->leftJoin('users','stock_transfer_requests.request_by_user_id','users.id')
                    ->leftJoin('warehouses','stock_transfer_requests.request_to_warehouse_id','warehouses.id')
                    ->leftJoin('stores','stock_transfer_requests.request_from_store_id','stores.id')
                    ->select('stock_transfer_requests.id','stock_transfer_requests.invoice_no','stock_transfer_requests.sub_total_amount','stock_transfer_requests.grand_total_amount','stock_transfer_requests.request_date as date','stock_transfer_requests.created_at as date_time','users.name as user_name','warehouses.id as warehouse_id','warehouses.name as warehouse_name','stores.id as store_id','stores.name as store_name','stores.phone as store_phone','stores.email as store_email','stores.address as store_address')
                    ->orderBy('stock_transfer_requests.id','desc')
                    ->paginate(12);
            }

            if($stock_transfer_request_lists === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Stock Transfer Request Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$stock_transfer_request_lists);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeToWarehouseStockRequestDetails(Request $request){
        try {
            $stock_transfer_request_details = DB::table('stock_transfer_requests')
                ->join('stock_transfer_request_details','stock_transfer_requests.id','stock_transfer_request_details.stock_transfer_request_id')
                ->leftJoin('products','stock_transfer_request_details.product_id','products.id')
                ->leftJoin('product_units','stock_transfer_request_details.product_unit_id','product_units.id')
                ->leftJoin('product_brands','stock_transfer_request_details.product_brand_id','product_brands.id')
                ->where('stock_transfer_requests.id',$request->stock_transfer_request_id)
                ->select(
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id',
                    'stock_transfer_requests.request_from_store_id',
                    'stock_transfer_requests.request_to_warehouse_id',
                    'stock_transfer_request_details.request_qty as qty',
                    'stock_transfer_request_details.id as stock_transfer_request_detail_id',
                    'stock_transfer_request_details.price as purchase_price',
                    'stock_transfer_request_details.sub_total',
                    'stock_transfer_request_details.vat_amount'
                )
                ->get();

            if(count($stock_transfer_request_details) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Stock Transfer Request Details Found.',null);
                return response()->json($response,404);
            }else{
                $store_stock_request_arr = [];
                foreach ($stock_transfer_request_details as $stock_transfer_request_detail){
                    $current_stock = warehouseProductCurrentStockByWarehouseAndProduct($stock_transfer_request_detail->request_to_warehouse_id,$stock_transfer_request_detail->product_id);
                    $product = Product::find($stock_transfer_request_detail->product_id);

                    $nested_data['product_id']=$stock_transfer_request_detail->product_id;
                    $nested_data['product_name']=$stock_transfer_request_detail->product_name;
                    $nested_data['product_category_id'] = $stock_transfer_request_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $stock_transfer_request_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$stock_transfer_request_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$stock_transfer_request_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $stock_transfer_request_detail->product_size_id;
                    $nested_data['product_size_name'] = $product->size ? $product->size->name : '';
                    $nested_data['qty']=$stock_transfer_request_detail->qty;
                    $nested_data['stock_transfer_request_detail_id']=$stock_transfer_request_detail->stock_transfer_request_detail_id;
                    $nested_data['purchase_price']=$stock_transfer_request_detail->purchase_price;
                    $nested_data['sub_total']=$stock_transfer_request_detail->sub_total;
                    $nested_data['vat_amount']=$stock_transfer_request_detail->vat_amount;
                    $nested_data['current_stock']=$current_stock;

                    array_push($store_stock_request_arr,$nested_data);
                }
                $response = APIHelpers::createAPIResponse(false,200,'',$store_stock_request_arr);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeToWarehouseStockRequestDetailsPrint(Request $request){
        try {
            $stock_transfer_request_details = DB::table('stock_transfer_requests')
                ->join('stock_transfer_request_details','stock_transfer_requests.id','stock_transfer_request_details.stock_transfer_request_id')
                ->leftJoin('products','stock_transfer_request_details.product_id','products.id')
                ->leftJoin('product_units','stock_transfer_request_details.product_unit_id','product_units.id')
                ->leftJoin('product_brands','stock_transfer_request_details.product_brand_id','product_brands.id')
                ->where('stock_transfer_requests.id',$request->stock_transfer_request_id)
                ->select(
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.product_unit_id',
                    'products.product_category_id',
                    'products.product_size_id',
                    'products.product_sub_unit_id',
                    'stock_transfer_requests.request_from_store_id',
                    'stock_transfer_requests.request_to_warehouse_id',
                    'stock_transfer_request_details.request_qty as qty',
                    'stock_transfer_request_details.id as stock_transfer_request_detail_id',
                    'stock_transfer_request_details.price as purchase_price',
                    'stock_transfer_request_details.sub_total',
                    'stock_transfer_request_details.vat_amount'
                )
                ->get();

            if(count($stock_transfer_request_details) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Stock Transfer Request Details Found.',null);
                return response()->json($response,404);
            }else{
                $store_stock_request_arr = [];
                foreach ($stock_transfer_request_details as $stock_transfer_request_detail){
                    $current_stock = warehouseProductCurrentStockByWarehouseAndProduct($stock_transfer_request_detail->request_to_warehouse_id,$stock_transfer_request_detail->product_id);
                    $product = Product::find($stock_transfer_request_detail->product_id);

                    $nested_data['product_id']=$stock_transfer_request_detail->product_id;
                    $nested_data['product_name']=$stock_transfer_request_detail->product_name;
                    $nested_data['product_category_id'] = $stock_transfer_request_detail->product_category_id;
                    $nested_data['product_category_name'] = $product->category->name;
                    $nested_data['product_unit_id'] = $stock_transfer_request_detail->product_unit_id;
                    $nested_data['product_unit_name'] = $product->unit->name;
                    $nested_data['product_sub_unit_id']=$stock_transfer_request_detail->product_sub_unit_id;
                    $nested_data['product_sub_unit_name']=$stock_transfer_request_detail->product_sub_unit_id ? $product->sub_unit->name : '';
                    $nested_data['product_size_id'] = $stock_transfer_request_detail->product_size_id;
                    $nested_data['product_size_name'] = $product->size ? $product->size->name : '';
                    $nested_data['qty']=$stock_transfer_request_detail->qty;
                    $nested_data['stock_transfer_request_detail_id']=$stock_transfer_request_detail->stock_transfer_request_detail_id;
                    $nested_data['purchase_price']=$stock_transfer_request_detail->purchase_price;
                    $nested_data['sub_total']=$stock_transfer_request_detail->sub_total;
                    $nested_data['vat_amount']=$stock_transfer_request_detail->vat_amount;
                    $nested_data['current_stock']=$current_stock;

                    array_push($store_stock_request_arr,$nested_data);
                }

                $store_and_warehouse_details = DB::table('stock_transfer_requests')
                    ->join('warehouses','stock_transfer_requests.request_to_warehouse_id','warehouses.id')
                    ->join('stores','stock_transfer_requests.request_from_store_id','stores.id')
                    ->where('stock_transfer_requests.id',$request->stock_transfer_request_id)
                    ->select(
                        'stores.id as store_id',
                        'stores.name as store_name',
                        'stores.phone as store_phone',
                        'stores.address as store_address',
                        'warehouses.id as warehouse_id',
                        'warehouses.name as warehouse_name',
                        'warehouses.phone as warehouse_phone',
                        'warehouses.address as warehouse_address'
                    )
                    ->first();

                return response()->json(['success' => true,'code' => 200,'data' => $store_stock_request_arr, 'info' => $store_and_warehouse_details], 200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeToWarehouseStockRequestDelete(Request $request){
        try {
            $check_exists_stock_transfer_request = DB::table("stock_transfer_requests")->where('id',$request->stock_transfer_request_id)->pluck('id')->first();
            if($check_exists_stock_transfer_request == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Stock Transfer Request Found.',null);
                return response()->json($response,404);
            }

            $stockTransferRequest = StockTransferRequest::find($request->stock_transfer_request_id);
            $delete_stockTransferRequest = $stockTransferRequest->delete();

            DB::table('stock_transfer_request_details')->where('stock_transfer_request_id',$request->stock_transfer_request_id)->delete();

            if($delete_stockTransferRequest)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Stock Transfer Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Stock Transfer Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

}
