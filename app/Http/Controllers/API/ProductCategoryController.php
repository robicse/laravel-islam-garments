<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends Controller
{
    // product category
    public function productCategoryActiveList(){
        try {
            $product_categories = DB::table('product_categories')->select('id','name','status')->where('status',1)->get();
            if($product_categories === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Size Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$product_categories);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productCategoryList(){
        try {
            $product_categories = DB::table('product_categories')->select('id','name','status')->get();
            if($product_categories === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Size Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$product_categories);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productCategoryCreate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:product_categories,name',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $product_category = new ProductCategory();
            $product_category->name = $request->name;
            $product_category->status = $request->status;
            $product_category->save();

            $response = APIHelpers::createAPIResponse(false,201,'Product Category Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productCategoryEdit(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'product_category_id'=> 'required',
                'name' => 'required|unique:product_categories,name,'.$request->product_category_id,
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $check_exists_product_category = DB::table("product_categories")->where('id',$request->product_category_id)->pluck('id')->first();
            if($check_exists_product_category == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Category Found.',null);
                return response()->json($response,404);
            }

            $product_category = ProductCategory::find($request->product_category_id);
            $product_category->name = $request->name;
            $product_category->status = $request->status;
            $update_product_category = $product_category->save();

            if($update_product_category){
                $response = APIHelpers::createAPIResponse(false,200,'Product Category Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Category Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productCategoryDelete(Request $request){
        try {
            $check_exists_product_category = DB::table("product_categories")->where('id',$request->product_category_id)->pluck('id')->first();
            if($check_exists_product_category == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Category Found.',null);
                return response()->json($response,404);
            }

            $soft_delete_product_category = ProductCategory::find($request->product_category_id);
            $soft_delete_product_category->status=0;
            $affected_row = $soft_delete_product_category->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Product Category Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Category Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
