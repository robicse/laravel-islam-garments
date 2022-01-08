<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\AccessLog;
use App\Model\Supplier;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class SupplierController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:vendor-list|vendor-create|vendor-edit|vendor-delete', ['only' => ['index','store']]);
//        $this->middleware('permission:vendor-create', ['only' => ['create','store']]);
//        $this->middleware('permission:vendor-edit', ['only' => ['edit','update']]);
//        $this->middleware('permission:vendor-delete', ['only' => ['destroy']]);
//    }

    public function index()
    {
        $suppliers = Supplier::all();
        return view('backend.admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('backend.admin.suppliers.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            //'name'=> 'required|unique:vendors,name',
        ]);

        $get_supplier_code = Supplier::latest('id','desc')->pluck('supplier_code')->first();
        if(!empty($get_supplier_code)){
            $get_supplier_code_after_replace = str_replace("SC-","",$get_supplier_code);
            $supplier_code = $get_supplier_code_after_replace+1;
        }else{
            $supplier_code = 1;
        }
        $final_supplier_code = 'SC-'.$supplier_code;

        $supplier = new Supplier();
        $supplier->name = $request->name;
        $supplier->supplier_code = $final_supplier_code;
        $supplier->phone = $request->phone;
        $supplier->email = $request->email;
        $supplier->supplier_address = $request->supplier_address;
        $supplier->save();
        $insert_id = $supplier->id;
        if($insert_id){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Supplier';
            $accessLog->action_done='Create';
            $accessLog->action_remarks='Supplier ID: '.$insert_id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Supplier Created Successfully');
        return redirect()->route('admin.suppliers.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $supplier = Supplier::find($id);
        return view('backend.admin.suppliers.edit',compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            //'name'=> 'required|unique:suppliers,name,'.$id,
        ]);

        $supplier = Supplier::find($id);
        $supplier->name = $request->name;
        $supplier->phone = $request->phone;
        $supplier->email = $request->email;
        $supplier->supplier_address = $request->supplier_address;
        $updated_row =$supplier->save();
        if($updated_row){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Supplier';
            $accessLog->action_done='Update';
            $accessLog->action_remarks='Supplier ID: '.$id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Supplier updated successfully','Success');
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
//            $accessLog->action_module='Vendor';
//            $accessLog->action_done='Delete';
//            $accessLog->action_remarks='Vendor ID: '.$id;
//            $accessLog->action_date=date('Y-m-d');
//            $accessLog->save();
//        }
//
//        Toastr::success('Vendor deleted successfully','Success');
//        return back();
//    }
}
