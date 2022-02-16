<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Product;
use App\ProductCategory;
use App\ProductSize;
use App\ProductUnit;
use App\ProductVat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
    public $successStatus = 200;
    public $authStatus = 401;
    public $failStatus = 402;
    public $ExistsStatus = 403;
    public $validationStatus = 404;

    // product category
    public function productActiveList(){
        try {
            $products = DB::table('products')->select('id','name','status')->where('status',1)->get();
            if($products === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$products);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productListBarcode(){
        try {
            $products = DB::table('products')
                ->leftJoin('product_units','products.product_unit_id','product_units.id')
                ->leftJoin('product_brands','products.product_brand_id','product_brands.id')
                ->select(
                    'products.id',
                    'products.name as product_name',
                    'products.image',
                    'product_units.id as unit_id',
                    'product_units.name as unit_name',
                    'products.item_code',
                    'products.barcode',
                    'products.self_no',
                    'products.low_inventory_alert',
                    'product_brands.id as brand_id',
                    'product_brands.name as brand_name',
                    'products.purchase_price',
                    'products.whole_sale_price as whole_sale_price',
                    'products.selling_price',
                    'products.note',
                    'products.date',
                    'products.status',
                    'products.vat_status',
                    'products.vat_percentage',
                    'products.vat_amount',
                    'products.vat_whole_amount'
                )
                ->orderBy('products.id','desc')
                ->get();

            $data = [];
            if($products)
            {
                foreach($products as $product){

                    $warehouse_current_stock = DB::table('warehouse_current_stocks')
                        ->where('product_id',$product->id)
                        ->latest('id')
                        ->pluck('current_stock')
                        ->first();

                    if($warehouse_current_stock == NULL){
                        $warehouse_current_stock = 0;
                    }

                    $nested_data['id']=$product->id;
                    $nested_data['product_name']=$product->product_name;
                    $nested_data['image']=$product->image;
                    $nested_data['unit_id']=$product->unit_id;
                    $nested_data['unit_name']=$product->unit_name;
                    $nested_data['item_code']=$product->item_code;
                    $nested_data['barcode']=$product->barcode;
                    $nested_data['self_no']=$product->self_no;
                    $nested_data['low_inventory_alert']=$product->low_inventory_alert;
                    $nested_data['brand_id']=$product->brand_id;
                    $nested_data['brand_name']=$product->brand_name;
                    $nested_data['purchase_price']=$product->purchase_price;
                    $nested_data['whole_sale_price']=$product->whole_sale_price;
                    $nested_data['selling_price']=$product->selling_price;
                    $nested_data['note']=$product->note;
                    $nested_data['date']=$product->date;
                    $nested_data['status']=$product->status;
                    $nested_data['vat_status']=$product->vat_status;
                    $nested_data['vat_percentage']=$product->vat_percentage;
                    $nested_data['vat_amount']=$product->vat_amount;
                    $nested_data['vat_whole_amount']=$product->vat_whole_amount;
                    $nested_data['warehouse_current_stock']=$warehouse_current_stock;

                    array_push($data,$nested_data);
                }

                $response = APIHelpers::createAPIResponse(false,200,'',$data);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,404,'No Product List Found.',null);
                return response()->json($response,404);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productList(Request $request){
        return new ProductCollection(Product::latest()->paginate(12));
    }

    public function productListWithSearch(Request $request){
        try {
            if($request->search){
                $products = Product::where('name','like','%'.$request->search.'%')
                    ->orWhere('name','like','%'.$request->search.'%')
                    ->orWhere('product_code','like','%'.$request->search.'%')
                    ->orWhere('barcode','like','%'.$request->search.'%')
                    ->orWhere('whole_sale_price','like','%'.$request->search.'%')
                    ->orWhere('selling_price','like','%'.$request->search.'%')
                    ->latest()->paginate(12);
                if($products === null){
                    $response = APIHelpers::createAPIResponse(true,404,'No Product Found.',null);
                    return response()->json($response,404);
                }else{
                    return new ProductCollection($products);
                }
            }else{
                return new ProductCollection(Product::latest()->paginate(12));
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function barcodeProductList(Request $request){

        if($request->count == 0){
            $products = DB::table('products')
                ->leftJoin('product_units','products.product_unit_id','product_units.id')
                ->leftJoin('product_brands','products.product_brand_id','product_brands.id')
                ->select('products.id','products.name as product_name','products.image','product_units.id as unit_id','product_units.name as unit_name','products.item_code','products.barcode','products.self_no','products.low_inventory_alert','product_brands.id as brand_id','product_brands.name as brand_name','products.purchase_price','products.whole_sale_price as whole_sale_price','products.selling_price','products.note','products.date','products.status','products.vat_status','products.vat_percentage','products.vat_amount','products.vat_whole_amount')
                ->where('products.id', '>=',$request->first_product)
                //->limit($request->count)
                ->orderBy('products.id','desc')
                ->get();
        }else{
            $products = DB::table('products')
                ->leftJoin('product_units','products.product_unit_id','product_units.id')
                ->leftJoin('product_brands','products.product_brand_id','product_brands.id')
                ->select('products.id','products.name as product_name','products.image','product_units.id as unit_id','product_units.name as unit_name','products.item_code','products.barcode','products.self_no','products.low_inventory_alert','product_brands.id as brand_id','product_brands.name as brand_name','products.purchase_price','products.whole_sale_price as whole_sale_price','products.selling_price','products.note','products.date','products.status','products.vat_status','products.vat_percentage','products.vat_amount','products.vat_whole_amount')
                ->where('products.id', '>=',$request->first_product)
                ->limit($request->count)
                ->orderBy('products.id','desc')
                ->get();
        }

        if($products)
        {
            $success['products'] =  $products;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Product List Found!'], $this->failStatus);
        }
    }

    public function allActiveProductList(){
        $products = DB::table('products')
            ->leftJoin('product_units','products.product_unit_id','product_units.id')
            ->leftJoin('product_brands','products.product_brand_id','product_brands.id')
            ->where('products.status',1)
            ->select('products.id','products.name as product_name','products.image','product_units.id as unit_id','product_units.name as unit_name','products.item_code','products.barcode','products.self_no','products.low_inventory_alert','product_brands.id as brand_id','product_brands.name as brand_name','products.purchase_price','products.whole_sale_price','products.selling_price','products.note','products.date','products.status','products.vat_status','products.vat_percentage','products.vat_amount')
            ->orderBy('products.id','desc')
            ->get();

        if($products)
        {
            $success['products'] =  $products;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Product List Found!'], $this->failStatus);
        }
    }

    public function allActiveProductListBarcode(Request $request){
        $products = DB::table('products')
            ->leftJoin('product_units','products.product_unit_id','product_units.id')
            ->leftJoin('product_brands','products.product_brand_id','product_brands.id')
            ->where('products.barcode',$request->barcode)
            ->select('products.id','products.name as product_name','products.image','product_units.id as unit_id','product_units.name as unit_name','products.item_code','products.barcode','products.self_no','products.low_inventory_alert','product_brands.id as brand_id','product_brands.name as brand_name','products.purchase_price','products.whole_sale_price','products.selling_price','products.note','products.date','products.status','products.vat_status','products.vat_percentage','products.vat_amount')
            ->get();

        if($products)
        {
            $success['products'] =  $products;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Product List Found!'], $this->failStatus);
        }
    }

    public function allActiveProductListItemcode(Request $request){
        $products = DB::table('products')
            ->leftJoin('product_units','products.product_unit_id','product_units.id')
            ->leftJoin('product_brands','products.product_brand_id','product_brands.id')
            ->where('products.item_code',$request->item_code)
            ->select('products.id','products.name as product_name','products.image','product_units.id as unit_id','product_units.name as unit_name','products.item_code','products.barcode','products.self_no','products.low_inventory_alert','product_brands.id as brand_id','product_brands.name as brand_name','products.purchase_price','products.whole_sale_price','products.selling_price','products.note','products.date','products.status','products.vat_status','products.vat_percentage','products.vat_amount')
            ->get();

        if($products)
        {
            $success['products'] =  $products;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Product List Found!'], $this->failStatus);
        }
    }

    public function checkExistsProduct(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required',
                //'name' => 'required',
                'product_category_id'=> 'required',
                'product_unit_id'=> 'required',
                'product_size_id'=> 'required',
                'product_code'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $check_exists_product = DB::table("products")
                ->where('type',$request->type)
                //->where('name',$request->name)
                ->where('product_unit_id',$request->product_unit_id)
                ->where('product_category_id',$request->product_category_id)
                ->where('product_size_id',$request->product_size_id)
                ->where('product_code',$request->product_code)
                ->pluck('id')->first();
            if($check_exists_product == null){
                $response = APIHelpers::createAPIResponse(false,200,'No Product Found.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,409,'Product Already Exists.',null);
                return response()->json($response,409);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productInfoForStockIn(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required',
                //'name' => 'required',
                'product_category_id'=> 'required',
                'product_unit_id'=> 'required',
                'product_size_id'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $product_info = Product::where('type',$request->type)
                ->where('product_category_id',$request->product_category_id)
                ->where('product_unit_id',$request->product_unit_id)
                ->where('product_size_id',$request->product_size_id)
                ->latest()
                ->paginate(1);

            if(count($product_info) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Found.',null);
                return response()->json($response,404);
            }else{
                return new ProductCollection($product_info);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productCreate(Request $request){
        try {
            $fourRandomDigit = rand(1000,9999);
            $barcode = time().$fourRandomDigit;

            $validator = Validator::make($request->all(), [
                'product_category_id'=> 'required',
                'product_unit_id'=> 'required',
                'purchase_price'=> 'required',
                'whole_sale_price'=> 'required',
                'selling_price'=> 'required',
                'status'=> 'required',
                'vat_status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

//            $item_code = isset($request->item_code) ? $request->item_code : '';
//            if($item_code){
//                $check_exists = Product::where('item_code',$item_code)->pluck('id')->first();
//                if($check_exists){
//                    $response = APIHelpers::createAPIResponse(true,409,'This Item Code Is Already exists!',null);
//                    return response()->json($response,409);
//                }
//            }

            $product_vat = ProductVat::latest()->first();
            $vat_percentage = 0;
            $vat_amount = 0;
            $vat_whole_amount = 0;
            if($product_vat && ($request->vat_status == 1)){
                $vat_percentage = $product_vat->vat_percentage;
                if($request->selling_price > 0){
                    $vat_amount = $request->selling_price*$vat_percentage/100;
                }
                if($request->whole_sale_price > 0){
                    $vat_whole_amount = $request->whole_sale_price*$vat_percentage/100;
                }
            }

            $get_product_code = Product::latest('id','desc')->pluck('code')->first();
            if(!empty($get_product_code)){
                $get_product_code_after_replace = str_replace("PC-","",$get_product_code);
                $product_code = $get_product_code_after_replace+1;
            }else{
                $product_code = 1;
            }
            $final_product_code = 'PC-'.$product_code;
            $date = date('Y-m-d');

            $product_category = ProductCategory::where('id',$request->product_category_id)->pluck('name')->first();
            $product_unit = ProductUnit::where('id',$request->product_unit_id)->pluck('name')->first();
            $product_size = ProductSize::where('id',$request->product_size_id)->pluck('name')->first();
            $product_code = $request->product_code;
            $name = $product_category.'-'.$product_unit.'-'.$product_size.'-'.$product_code;

            $product = new Product();
            $product->type = $request->type;
            $product->product_category_id = $request->product_category_id;
            $product->product_unit_id = $request->product_unit_id;
            $product->product_size_id = $request->product_size_id;
            $product->name = $name;
            $product->code = $final_product_code;
            $product->product_code = $request->product_code;
            //$product->barcode = $request->barcode;
            $product->barcode = $barcode;
            $product->self_no = $request->self_no ? $request->self_no : NULL;
            $product->low_inventory_alert = $request->low_inventory_alert ? $request->low_inventory_alert : NULL;
            $product->product_brand_id = $request->product_brand_id ? $request->product_brand_id : NULL;
            $product->purchase_price = $request->purchase_price;
            $product->whole_sale_price = $request->whole_sale_price;
            $product->selling_price = $request->selling_price;
            $product->vat_status = $request->vat_status;
            $product->vat_percentage = $vat_percentage;
            $product->vat_amount = $vat_amount;
            $product->vat_whole_amount = $vat_whole_amount;
            $product->color = $request->color ? $request->color : NULL;
            $product->design = $request->design ? $request->design : NULL;
            $product->note = $request->note ? $request->note : NULL;
            $product->date = $date;
            $product->status = $request->status;
            $image = $request->file('image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $image_name = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

    //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/products/'. $image_name, $proImage);

                // update image db
                $product->image = $image_name;
            }else{
                $product->image = 'default.png';
            }
            $product->save();

            $response = APIHelpers::createAPIResponse(false,201,'Product Added Successfully.',$product->id,null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productEdit(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'product_id'=> 'required',
//                'name' => 'required|unique:products,name,'.$request->product_id,
//                'product_category_id'=> 'required',
//                'product_unit_id'=> 'required',
//                'purchase_price'=> 'required',
//                'whole_sale_price'=> 'required',
//                'selling_price'=> 'required',
//                'date'=> 'required',
//                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $check_exists_product = DB::table("products")->where('id',$request->product_id)->pluck('id')->first();
            if($check_exists_product == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Warehouse Found.',null);
                return response()->json($response,404);
            }

            $product = Product::find($request->product_id);
            $product->product_code = $request->product_code ? $request->product_code : $product->product_code;
            $product->purchase_price = $request->purchase_price;
            $product->whole_sale_price = $request->purchase_price;
            $product->selling_price = $request->purchase_price;
            $product->color = $request->color ? $request->color : '';
            $product->design = $request->design ? $request->design : '';
            $product->note = $request->note ? $request->note : '';
            $image = $request->file('image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $image_name = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/products/'. $image_name, $proImage);

                // update image db
                $product->image = $image_name;
            }else{
                $product->image = Product::where('id',$request->product_id)->pluck('image')->first();
            }
            $update_product = $product->save();

            if($update_product){
                $response = APIHelpers::createAPIResponse(false,200,'Product Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productDelete(Request $request){
        try {
            $check_exists_product = DB::table("products")->where('id',$request->product_id)->pluck('id')->first();
            if($check_exists_product == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Found.',null);
                return response()->json($response,404);
            }

            //$delete_product = DB::table("products")->where('id',$request->product_id)->delete();
            $soft_delete_product = Product::find($request->product_id);
            $soft_delete_product->status=0;
            $affected_row = $soft_delete_product->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Product Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Product Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function productImage(Request $request)
    {
        $product=Product::find($request->product_id);
        $base64_image_propic = $request->pro_img;
        //return response()->json(['response' => $base64_image_propic], $this-> successStatus);

        $data = $request->pro_img;
        $pos = strpos($data, ';');
        $type = explode(':', substr($data, 0, $pos))[1];
        $type1 = explode('/', $type)[1];

        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image_propic)) {
            $data = substr($base64_image_propic, strpos($base64_image_propic, ',') + 1);
            $data = base64_decode($data);

            $currentDate = Carbon::now()->toDateString();
            $imagename = $currentDate . '-' . uniqid() . 'product_pic.'.$type1 ;

            // delete old image.....
            if(Storage::disk('public')->exists('uploads/products/'.$product->image))
            {
                Storage::disk('public')->delete('uploads/products/'.$product->image);

            }

            // resize image for service category and upload
            //$data = Image::make($data)->resize(100, 100)->save($data->getClientOriginalExtension());

            // store image
            Storage::disk('public')->put("uploads/products/". $imagename, $data);


            // update image db
            $product->image = $imagename;
            $product->update();

            $success['product'] = $product;
            return response()->json(['response' => $success], $this-> successStatus);

        }else{
            return response()->json(['response'=>'failed'], $this-> failStatus);
        }

    }
}
