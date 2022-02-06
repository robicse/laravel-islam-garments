<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductSizeController extends Controller
{
    // product size
    public function productSizeActiveList(){
        try {
            $product_sizes = DB::table('product_sizes')->select('id','name','status')->where('status',1)->get();
            if($product_sizes === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Size Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$product_sizes);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSizeList(){
        try {
            $product_sizes = DB::table('product_sizes')->select('id','name','status')->get();
            if($product_sizes === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Size Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$product_sizes);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSizeCreate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:product_sizes,name',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }


            $product_size = new ProductSize();
            $product_size->name = $request->name;
            $product_size->status = $request->status;
            $product_size->save();

            $response = APIHelpers::createAPIResponse(false,201,'Product Size Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSizeEdit(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'product_size_id'=> 'required',
                'name' => 'required|unique:product_sizes,name,'.$request->product_size_id,
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $check_exists_product_size = DB::table("product_sizes")->where('id',$request->product_size_id)->pluck('id')->first();
            if($check_exists_product_size == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Size Found.',null);
                return response()->json($response,404);
            }

            $product_sizes = ProductSize::find($request->product_size_id);
            $product_sizes->name = $request->name;
            $product_sizes->status = $request->status;
            $update_product_size = $product_sizes->save();

            if($update_product_size){
                $response = APIHelpers::createAPIResponse(false,200,'Product Size Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Size Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productSizeDelete(Request $request){
        try {
            $check_exists_product_size = DB::table("product_sizes")->where('id',$request->product_size_id)->pluck('id')->first();
            if($check_exists_product_size == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Size Found.',null);
                return response()->json($response,404);
            }

            $soft_delete_product_size = ProductSize::find($request->product_size_id);
            $soft_delete_product_size->status=0;
            $affected_row = $soft_delete_product_size->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Product Size Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Size Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
