<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    public function storeList(){
        try {
            $stores = DB::table('stores')
                ->select('stores.id','stores.name as store_name','stores.phone','stores.email','stores.address','stores.status')
                ->get();
            if($stores === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Store Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$stores);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeActiveList(){
        try {
            $stores = DB::table('stores')
                ->select('stores.id','stores.name as store_name','stores.phone','stores.email','stores.address','stores.status')
                ->where('status',1)
                ->get();
            if($stores === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Store Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$stores);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeCreate(Request $request){

        try {
            // required and unique
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'phone' => 'required|unique:stores,name',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,409,$validator->errors(),null);
                return response()->json($response,409);
            }

            $store = new Store();
            $store->name = $request->name;
            $store->phone = $request->phone;
            $store->email = $request->email ? $request->email : NULL;
            $store->address = $request->address ? $request->address : NULL;
            $store->status = $request->status;
            $store->save();

            $response = APIHelpers::createAPIResponse(false,201,'Store Added Successfully.',null);
            return response()->json($response,201);

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeEdit(Request $request){
        try {
            $check_exists_store = DB::table("stores")->where('id',$request->store_id)->pluck('id')->first();
            if($check_exists_store == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Store Found.',null);
                return response()->json($response,404);
            }

            // required and unique
            $validator = Validator::make($request->all(), [
                'name' => 'unique:stores,name,'.$request->store_id,
                'phone' => 'required',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,409,$validator->errors(),null);
                return response()->json($response,409);
            }

            $store = Store::find($request->store_id);
            $store->name = $request->name;
            $store->phone = $request->phone;
            $store->email = $request->email ? $request->email : NULL;
            $store->address = $request->address ? $request->address : NULL;
            $store->status = $request->status;
            $update_store = $store->save();
            if($update_store){
                $response = APIHelpers::createAPIResponse(false,200,'Store Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Store Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function storeDelete(Request $request){
        try {
            $check_exists_store = DB::table("stores")->where('id',$request->store_id)->pluck('id')->first();
            if($check_exists_store == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Store Found.',null);
                return response()->json($response,404);
            }

            $store_soft_delete = Store::find($request->store_id);
            $store_soft_delete->status=0;
            $affected_row = $store_soft_delete->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Store Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Store Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
