<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    // product warehouse
    public function warehouseActiveList(){
        try {
            $warehouses = DB::table('warehouses')->select('id','name','phone','email','address','status')->where('status',1)->get();
            if($warehouses === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$warehouses);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    // product warehouse
    public function warehouseList(){
        try {
            $warehouses = DB::table('warehouses')->select('id','name','phone','email','address','status')->get();
            if($warehouses === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$warehouses);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function warehouseCreate(Request $request){

        try {
            // required and unique
            $validator = Validator::make($request->all(), [
                'name' => 'unique:warehouses,name',
                'phone' => 'required',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $warehouse = new Warehouse();
            $warehouse->name = $request->name;
            $warehouse->phone = $request->phone;
            $warehouse->email = $request->email;
            $warehouse->address = $request->address;
            $warehouse->status = $request->status;
            $warehouse->save();

            $response = APIHelpers::createAPIResponse(false,201,'Warehouse Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function warehouseEdit(Request $request){
        try {
            $check_exists_warehouse = DB::table("warehouses")->where('id',$request->warehouse_id)->pluck('id')->first();
            if($check_exists_warehouse == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Found.',null);
                return response()->json($response,404);
            }

            // required and unique
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'required',
                'name' => 'unique:warehouses,name,'.$request->warehouse_id,
                'phone' => 'required',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $warehouse = Warehouse::find($request->warehouse_id);
            $warehouse->name = $request->name;
            $warehouse->phone = $request->phone;
            $warehouse->email = $request->email;
            $warehouse->address = $request->address;
            $warehouse->status = $request->status;
            $update_warehouse = $warehouse->save();

            if($update_warehouse){
                $response = APIHelpers::createAPIResponse(false,200,'Warehouse Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Warehouse Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function warehouseDelete(Request $request){
        try {
            $check_exists_warehouse = DB::table("warehouses")->where('id',$request->warehouse_id)->pluck('id')->first();
            if($check_exists_warehouse == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Found.',null);
                return response()->json($response,404);
            }

            //$delete_warehouse = DB::table("warehouses")->where('id',$request->warehouse_id)->delete();
            $soft_delete_warehouse = Warehouse::find($request->warehouse_id);
            $soft_delete_warehouse->status=0;
            $affected_row = $soft_delete_warehouse->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Warehouse Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Warehouse Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

}
