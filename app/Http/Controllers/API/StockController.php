<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;

use App\Stock;
use App\WarehouseCurrentStock;
use App\WarehouseStoreCurrentStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
//    public function checkWarehouseProductCurrentQty(Request $request){
//        try {
//        $validator = Validator::make($request->all(), [
//            'type' => 'required',
//            'product_category_id'=> 'required',
//            'product_unit_id'=> 'required',
//            'product_size_id'=> 'required',
//            'warehouse_id'=> 'required',
//        ]);
//
//        if ($validator->fails()) {
//            $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
//            return response()->json($response,400);
//        }
//
//
//        $check_exists_product = checkExistsProduct($request->type,$request->product_category_id,$request->product_size_id,$request->product_unit_id,$request->product_sub_unit_id,$request->product_code);
//
//        if($check_exists_product === null){
//            $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Product Found.',null);
//            return response()->json($response,404);
//        }else{
//            $current_stock = WarehouseCurrentStock::where('warehouse_id',$request->warehouse_id)->where('product_id',$check_exists_product)->pluck('current_stock')->first();
//            $response = APIHelpers::createAPIResponse(false,200,'',$current_stock);
//            return response()->json($response,200);
//        }
//        } catch (\Exception $e) {
//            //return $e->getMessage();
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
//    }

    public function stockTransactionListWithSearch(Request $request){
        try {
            $warehouse_stock_list = Stock::leftJoin('users','stocks.user_id', '=', 'users.id')
                ->join('products','stocks.product_id','products.id')
                ->leftJoin('warehouses','stocks.warehouse_id','warehouses.id')
                ->leftJoin('product_categories','products.product_category_id','product_categories.id')
                ->leftJoin('product_units','products.product_unit_id','product_units.id')
                ->leftJoin('product_sizes','products.product_size_id','product_sizes.id')
                ->where('stocks.stock_where','warehouse')
                ->orWhere('products.name','like','%'.$request->search.'%')
                ->orWhere('product_categories.name','like','%'.$request->search.'%')
                ->orWhere('product_units.name','like','%'.$request->search.'%')
                ->orWhere('product_sizes.name','like','%'.$request->search.'%')
                ->select(
                    'stocks.id as stock_id',
                    'users.name as stock_by_user',
                    'warehouses.name as warehouse_name',
                    'product_categories.name as product_category_name',
                    'product_units.name as product_unit_name',
                    'product_sizes.name as product_size_name',
                    'products.name as product_name',
                    'stocks.stock_type',
                    'stocks.stock_where',
                    'stocks.stock_in_out',
                    'stocks.previous_stock',
                    'stocks.stock_in',
                    'stocks.stock_out',
                    'stocks.current_stock',
                    'stocks.stock_date',
                    'stocks.stock_date_time'
                )
                ->latest('stocks.id','desc')
                ->paginate(10);
            if($warehouse_stock_list === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Stock List Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$warehouse_stock_list);
                return response()->json($response,200);
            }

        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function warehouseCurrentStockById(Request $request){
        try {
            $warehouse_current_stock = WarehouseCurrentStock::join('products','warehouse_current_stocks.product_id','products.id')
                ->leftJoin('warehouses','warehouse_current_stocks.warehouse_id','warehouses.id')
                ->leftJoin('product_categories','products.product_category_id','product_categories.id')
                ->leftJoin('product_units','products.product_unit_id','product_units.id')
                ->leftJoin('product_sizes','products.product_size_id','product_sizes.id')
                ->where('warehouse_current_stocks.product_id',$request->warehouse_id)
                ->select(
                    'warehouses.name as warehouse_name',
                    'product_categories.name as product_category_name',
                    'product_units.name as product_unit_name',
                    'product_sizes.name as product_size_name',
                    'products.name as product_name',
                    'products.purchase_price as purchase_price',
                    'products.product_code as product_code',
                    'products.name as product_name',
                    'warehouse_current_stocks.current_stock'
                )
                ->latest('warehouse_current_stocks.id')
                ->paginate(12);
            if(count($warehouse_current_stock) == 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Current Stock Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$warehouse_current_stock);
                return response()->json($response,200);
            }

        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeCurrentStockById(Request $request){
        try {
            $warehouse_store_current_stock = WarehouseStoreCurrentStock::join('products','warehouse_store_current_stocks.product_id','products.id')
                ->leftJoin('stores','warehouse_store_current_stocks.warehouse_id','stores.id')
                ->leftJoin('product_categories','products.product_category_id','product_categories.id')
                ->leftJoin('product_units','products.product_unit_id','product_units.id')
                ->leftJoin('product_sizes','products.product_size_id','product_sizes.id')
                ->where('warehouse_store_current_stocks.product_id',$request->store_id)
                ->select(
                    'stores.name as store_name',
                    'product_categories.name as product_category_name',
                    'product_units.name as product_unit_name',
                    'product_sizes.name as product_size_name',
                    'products.name as product_name',
                    'products.purchase_price as purchase_price',
                    'products.product_code as product_code',
                    'products.name as product_name',
                    'warehouse_store_current_stocks.current_stock'
                )
                ->latest('warehouse_store_current_stocks.id')
                ->paginate(12);
            if(count($warehouse_store_current_stock) == 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Store Current Stock Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$warehouse_store_current_stock);
                return response()->json($response,200);
            }

        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
