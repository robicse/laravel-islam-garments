<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\AccessLog;
use App\Model\Customer;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CustomerController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:customer-list|customer-create|customer-edit|customer-delete', ['only' => ['index','store']]);
//        $this->middleware('permission:customer-create', ['only' => ['create','store']]);
//        $this->middleware('permission:customer-edit', ['only' => ['edit','update']]);
//        $this->middleware('permission:customer-delete', ['only' => ['destroy']]);
//    }

    public function index()
    {
        $customers = Customer::all();
        return view('backend.admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('backend.admin.customers.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            //'name'=> 'required|unique:vendors,name',
        ]);

        $get_customer_code = Customer::latest('id','desc')->pluck('customer_code')->first();
        if(!empty($get_customer_code)){
            $get_customer_code_after_replace = str_replace("CC-","",$get_customer_code);
            $customer_code = $get_customer_code_after_replace+1;
        }else{
            $customer_code = 1;
        }
        $final_customer_code = 'CC-'.$customer_code;

        $customer = new Customer();
        $customer->name = $request->name;
        $customer->customer_code = $final_customer_code;
        $customer->phone = $request->phone;
        $customer->email = $request->email;
        $customer->customer_address = $request->customer_address;
        $customer->save();
        $insert_id = $customer->id;
        if($insert_id){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Customer';
            $accessLog->action_done='Create';
            $accessLog->action_remarks='Customer ID: '.$insert_id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Customer Created Successfully');
        return redirect()->route('admin.customers.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $customer = Customer::find($id);
        return view('backend.admin.customers.edit',compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            //'name'=> 'required|unique:customers,name,'.$id,
        ]);

        $customer = Customer::find($id);
        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->email = $request->email;
        $customer->customer_address = $request->customer_address;
        $updated_row = $customer->save();
        if($updated_row){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Customer';
            $accessLog->action_done='Update';
            $accessLog->action_remarks='Customer ID: '.$id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Customer updated successfully','Success');
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
//            $accessLog->action_module='Customer';
//            $accessLog->action_done='Delete';
//            $accessLog->action_remarks='Customer ID: '.$id;
//            $accessLog->action_date=date('Y-m-d');
//            $accessLog->save();
//        }
//
//        Toastr::success('Vendor deleted successfully','Success');
//        return back();
//    }

//    public function banCustomer($id){
//        $user =  User::find($id);
//        $user->banned = 1;
//        $user->save();
//        Toastr::success('Customer has been banned Successfully','Success');
//        return redirect()->back();
//    }
}
