<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\ProductSubUnit;
use App\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductSubUnitController extends Controller
{
    // product unit
    public function productSubUnitActiveList(){
        try {
            $product_sub_units = DB::table('product_sub_units')->select('id','name','status')->where('status',1)->get();
            if($product_sub_units === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Sub Unit Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$product_sub_units);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSubUnitList(){
        try {
            $product_sub_units = DB::table('product_sub_units')
                ->join('product_units','product_sub_units.product_unit_id','product_units.id')
                ->select('product_units.name as unit_name','product_sub_units.id','product_sub_units.name as sub_unit_name','product_sub_units.status')
                ->get();
            if($product_sub_units === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Sub Unit Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$product_sub_units);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSubUnitCreate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:product_sub_units,name',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }


            $product_sub_unit = new ProductSubUnit();
            $product_sub_unit->product_unit_id = 2;
            $product_sub_unit->name = $request->name;
            $product_sub_unit->status = $request->status;
            $product_sub_unit->save();

            $response = APIHelpers::createAPIResponse(false,201,'Warehouse Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSubUnitEdit(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'product_sub_unit_id'=> 'required',
                'name' => 'required|unique:product_sub_units,name,'.$request->product_sub_unit_id,
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $check_exists_product_sub_unit = DB::table("product_sub_units")->where('id',$request->product_sub_unit_id)->pluck('id')->first();
            if($check_exists_product_sub_unit == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Sub Unit Found.',null);
                return response()->json($response,404);
            }

            $product_sub_unit = ProductSubUnit::find($request->product_sub_unit_id);
            $product_sub_unit->product_unit_id = 2;
            $product_sub_unit->name = $request->name;
            $product_sub_unit->status = $request->status;
            $update_product_sub_unit = $product_sub_unit->save();

            if($update_product_sub_unit){
                $response = APIHelpers::createAPIResponse(false,200,'Product Sub Unit Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Sub Unit Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSubUnitDelete(Request $request){
        try {
            $check_exists_product_sub_unit = DB::table("product_sub_units")->where('id',$request->product_sub_unit_id)->pluck('id')->first();
            if($check_exists_product_sub_unit == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Sub Unit Found.',null);
                return response()->json($response,404);
            }

            $soft_delete_product_sub_unit = ProductSubUnit::find($request->product_sub_unit_id);
            $soft_delete_product_sub_unit->status=0;
            $affected_row = $soft_delete_product_sub_unit->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Product Sub Unit Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Sub Unit Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
