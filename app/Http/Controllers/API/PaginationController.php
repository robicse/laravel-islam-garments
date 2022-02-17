<?php

namespace App\Http\Controllers\API;


use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PaginationController extends Controller
{

    public function warehouseCurrentStockListPaginationProductName(Request $request){
        try {
            $warehouse_stock_product = DB::table('warehouse_current_stocks')
                ->join('warehouses','warehouse_current_stocks.warehouse_id','warehouses.id')
                ->leftJoin('products','warehouse_current_stocks.product_id','products.id')
                ->where('warehouse_current_stocks.warehouse_id',$request->warehouse_id)
                ->where('warehouse_current_stocks.current_stock','>',0)
                ->where('products.name','like','%'.$request->name.'%')
                ->select('warehouse_current_stocks.*','warehouses.name as warehouse_name','products.name as product_name','products.purchase_price','products.selling_price','products.whole_sale_price','products.product_code','products.barcode','products.image','products.vat_status','products.vat_percentage','products.vat_amount')
                ->paginate(12);

            if(count($warehouse_stock_product) == 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Current Stock List Found!',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$warehouse_stock_product);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
