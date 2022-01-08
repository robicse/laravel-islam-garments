<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\AccessLog;
use App\Model\Product;
use App\Model\Brand;
use App\Model\Category;
use App\Model\VehicleDriverAssign;
use App\Model\Supplier;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:vehicle-list|vehicle-create|vehicle-edit|vehicle-delete', ['only' => ['index','store']]);
//        $this->middleware('permission:vehicle-create', ['only' => ['create','store']]);
//        $this->middleware('permission:vehicle-edit', ['only' => ['edit','update']]);
//        $this->middleware('permission:vehicle-delete', ['only' => ['destroy']]);
//    }

    public function index()
    {
        $products = Product::all();
        return view('backend.admin.products.index', compact('products'));
    }

    public function create()
    {
        $brands = Brand::all();
        $categories = Category::all();
        $suppliers = Supplier::where('status',1)->get();
        return view('backend.admin.products.create', compact('brands','categories','suppliers'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            //'name'=> 'required|unique:vehicles,name',
        ]);
        //dd($request->all());
        $get_vehicle_code = Vehicle::latest('id','desc')->pluck('vehicle_code')->first();
        if(!empty($get_vehicle_code)){
            $get_vehicle_code_after_replace = str_replace("VC-","",$get_vehicle_code);
            $vehicle_code = $get_vehicle_code_after_replace+1;
        }else{
            $vehicle_code = 1;
        }
        $final_vehicle_code = 'VC-'.$vehicle_code;

        if($request->vendor_id === NULL){
            $owner_name = 'Mr.';
            $vendor_id = NULL;
        }else{
            $owner_name = Vendor::where('id',$request->vendor_id)->pluck('name')->first();
            $vendor_id = $request->vendor_id;
        }

        $vehicle = new Vehicle();
        $vehicle->owner_name = $owner_name;
        $vehicle->vendor_id = $vendor_id;
        $vehicle->vehicle_name = $request->vehicle_name;
        $vehicle->vehicle_code = $final_vehicle_code;
        $vehicle->brand_id = $request->brand_id;
        $vehicle->category_id = $request->category_id;
        $vehicle->model = $request->model;
        //$vehicle->rent_type = $request->rent_type;
        //$vehicle->price = $request->price;
        $vehicle->registration_no = $request->registration_no;
        $vehicle->registration_date = $request->registration_date;
        $vehicle->chassis_no = $request->chassis_no;
        $vehicle->engine_no = $request->engine_no;
        $vehicle->vehicle_class = $request->vehicle_class;
        $vehicle->fuel_type = $request->fuel_type;
        $vehicle->fitness = $request->fitness;
        $vehicle->rc_status = $request->rc_status;
        $vehicle->own_vehicle_status = $request->own_vehicle_status;
        $vehicle->rent_type = $request->rent_type;
        $image = $request->file('image');
        if (isset($image)) {
            //make unique name for image
            $currentDate = Carbon::now()->toDateString();
            $imagename = $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
//            resize image for hospital and upload
            $proImage = Image::make($image)->resize(120, 80)->save($image->getClientOriginalExtension());
            Storage::disk('public')->put('uploads/vehicles/' . $imagename, $proImage);

        }else {
            $imagename = "default.png";
        }
        $vehicle->image = $imagename;
        $vehicle->save();
        $insert_id = $vehicle->id;
        if($insert_id){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Vehicle';
            $accessLog->action_done='Create';
            $accessLog->action_remarks='Vehicle ID: '.$insert_id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }
        Toastr::success('Vehicle Created Successfully');
        return redirect()->route('admin.vehicles.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $brands = Brand::all();
        $categories = Category::all();
        $product = Product::find($id);
        $suppliers = Supplier::where('status',1)->get();
        return view('backend.admin.vehicles.edit',compact('product','brands','categories','suppliers'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            //'name'=> 'required|unique:vehicles,name,'.$id,
        ]);

        if($request->vendor_id === NULL){
            $owner_name = 'Mr.';
            $vendor_id = NULL;
        }else{
            $owner_name = Vendor::where('id',$request->vendor_id)->pluck('name')->first();
            $vendor_id = $request->vendor_id;
        }

        $vehicle = Vehicle::find($id);
        $vehicle->vendor_id = $vendor_id;
        $vehicle->owner_name = $owner_name;
        $vehicle->vehicle_name = $request->vehicle_name;
        $vehicle->brand_id = $request->brand_id;
        $vehicle->category_id = $request->category_id;
        //$vehicle->rent_type = $request->rent_type;
        //$vehicle->price = $request->price;
        $vehicle->model = $request->model;
        $vehicle->registration_no = $request->registration_no;
        $vehicle->registration_date = $request->registration_date;
        $vehicle->chassis_no = $request->chassis_no;
        $vehicle->engine_no = $request->engine_no;
        $vehicle->vehicle_class = $request->vehicle_class;
        $vehicle->fuel_type = $request->fuel_type;
        $vehicle->fitness = $request->fitness;
        $vehicle->rc_status = $request->rc_status;
        $vehicle->own_vehicle_status = $request->own_vehicle_status;
        $vehicle->rent_type = $request->rent_type;
        $vehicle->status = $request->status;
        $image = $request->file('image');
        if (isset($image)) {
            //make unique name for image
            $currentDate = Carbon::now()->toDateString();
            $imagename = $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

            if(Storage::disk('public')->exists('uploads/vehicles/'.$vehicle->image))
            {
                Storage::disk('public')->delete('uploads/vehicles/'.$vehicle->image);
            }
//            resize image for hospital and upload
            $proImage = Image::make($image)->resize(120, 80)->save($image->getClientOriginalExtension());
            Storage::disk('public')->put('uploads/vehicles/' . $imagename, $proImage);

        }else {
            $imagename = $vehicle->image;
        }
        $vehicle->image = $imagename;
        $updated_row = $vehicle->save();
        if($updated_row){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Vehicle';
            $accessLog->action_done='Update';
            $accessLog->action_remarks='Vehicle ID: '.$id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Vehicle updated successfully','Success');
        return back();
    }

//    public function destroy($id)
//    {
//        $vendor = Vendor::find($id);
//        if(Storage::disk('public')->exists('uploads/vendors/'.$vendor->image))
//        {
//            Storage::disk('public')->delete('uploads/vendors/'.$vendor->image);
//        }
//        $deleted_row = $vendor->delete();
//        if($deleted_row){
//            $accessLog = new AccessLog();
//            $accessLog->user_id=Auth::user()->id;
//            $accessLog->action_module='Vehicle';
//            $accessLog->action_done='Delete';
//            $accessLog->action_remarks='Vehicle ID: '.$id;
//            $accessLog->action_date=date('Y-m-d');
//            $accessLog->save();
//        }
//
//        Toastr::success('Vendor deleted successfully','Success');
//        return back();
//    }
}
