<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\AccessLog;
use App\Model\Customer;
use App\Model\Order;
use App\Model\OrderItem;
use App\Model\Payment;
use App\Model\PaymentType;
use App\Model\Vehicle;
use App\Model\VehicleDriverAssign;
use App\Model\Vendor;
use NumberFormatter;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class OrderController extends Controller
{
    function __construct()
    {
        // vendor
        $this->middleware('permission:vehicle-vendor-rent-list|vehicle-vendor-rent-create|vehicle-vendor-rent-edit|vehicle-vendor-rent-delete', ['only' => ['vehicle_vendor_rent_list','vehicle_vendor_rent_store','vehicle_vendor_rent_show']]);
        $this->middleware('permission:vehicle-vendor-rent-create', ['only' => ['vehicle_vendor_rent_create','vehicle_vendor_rent_store']]);
        $this->middleware('permission:vehicle-vendor-rent-edit', ['only' => ['vehicle_vendor_rent_edit','vehicle_vendor_rent_update']]);
        $this->middleware('permission:vehicle-vendor-rent-delete', ['only' => ['destroy']]);

        // customer
        $this->middleware('permission:vehicle-customer-rent-list|vehicle-customer-rent-create|vehicle-customer-rent-edit|vehicle-customer-rent-delete', ['only' => ['vehicle_customer_rent_list','vehicle_customer_rent_store','vehicle_customer_rent_show']]);
        $this->middleware('permission:vehicle-customer-rent-create', ['only' => ['vehicle_vendor_rent_create','vehicle_customer_rent_store']]);
        $this->middleware('permission:vehicle-customer-rent-edit', ['only' => ['vehicle_vendor_rent_edit','vehicle_customer_rent_update']]);
        $this->middleware('permission:vehicle-customer-rent-delete', ['only' => ['destroy']]);
    }

    public function vehicle_vendor_rent_list()
    {
        $vehicleVendorRents = Order::where('type','Vendor')->get();
        $payment_types = PaymentType::where('name','!=','Credit')->get();
        return view('backend.admin.orders.vehicle_vendor_rent_list', compact('vehicleVendorRents','payment_types'));
    }

    public function vehicle_vendor_rent_create()
    {
        $vehicles = Vehicle::where('status',1)->where('own_vehicle_status','Rent')->get();
        $vendors = Vendor::where('status',1)->get();
        $payment_types = PaymentType::all();
        return view('backend.admin.orders.vehicle_vendor_rent_create', compact('vehicles','vendors','payment_types'));
    }

    public function vehicle_vendor_rent_store(Request $request)
    {
        //dd($request->all());
        $this->validate($request, [
            //'name'=> 'required|unique:vehicles,name',
        ]);



        //if($request->rent_type == 'Daily'){
            $start_year = date('Y', strtotime($request->start_date));
            $start_month = date('m', strtotime($request->start_date));
            $start_day = date('d', strtotime($request->start_date));

            $end_year = date('Y', strtotime($request->end_date));
            $end_month = date('m', strtotime($request->end_date));
            $end_day = date('d', strtotime($request->end_date));



            $date1 = $request->start_date;
            $date2 = $request->end_date;

            $ts1 = strtotime($date1);
            $ts2 = strtotime($date2);

            $year1 = date('Y', $ts1);
            $year2 = date('Y', $ts2);

            $month1 = date('m', $ts1);
            $month2 = date('m', $ts2);

            $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

            // Robeul CUSTOM  ADD 1
            $diff ++;
        //}





        $date = date('Y-m-d H:i:s');
        $vehicle = Vehicle::where('id',$request->vehicle_id)->first();
        $vehicleDriver = VehicleDriverAssign::where('vehicle_id',$request->vehicle_id)->first();

        // invoice no
        if($request->payment_type_id == 1){
            $get_invoice_no = Order::latest()->pluck('invoice_no')->where('payment_type_id',1)->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("CS-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1;
            }
            $final_invoice = 'CS-'.$invoice_no;
        }else{
            $get_invoice_no = Order::latest()->pluck('invoice_no')->where('payment_type_id',2)->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("CR-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1;
            }
            $final_invoice = 'CR-'.$invoice_no;
        }
        // invoice no

        $order = new Order();
        $order->date = $date;
        $order->invoice_no = $final_invoice;
        $order->order_type = 'Purchases';
        $order->vendor_id = $request->vendor_id;
        $order->type = 'Vendor';
        $order->payment_type_id = $request->payment_type_id;
        $order->sub_total = $request->sub_total;
        $order->discount_type = $request->discount_type;
        $order->discount_percent = $request->discount_percent;
        $order->discount_amount = $request->discount_amount;
        $order->grand_total = $request->grand_total;
        $order->paid = $request->payment_type_id == 1 ? $request->grand_total : $request->paid;
        $order->exchange = 0;
        $order->due_price = $request->payment_type_id == 1 ? 0 : $request->due_price;
        $order->status = 'Done';
        $order->save();

        $insert_id = $order->id;
        if($insert_id){
            $orderItem = new OrderItem();
            $orderItem->date = $date;
            $orderItem->order_id=$insert_id;
            $orderItem->vehicle_id=$request->vehicle_id;
            //$orderItem->driver_id=$vehicleDriver->driver_id;
            $orderItem->rent_type=$request->rent_type;
            $orderItem->start_year=$start_year;
            $orderItem->end_year=$end_year;
            $orderItem->start_month=$start_month;
            $orderItem->end_month=$end_month;
            $orderItem->start_date=$request->start_date;
            $orderItem->end_date=$request->end_date;
            $orderItem->rent_duration_month=$diff;
            $orderItem->rent_duration_day=$request->rent_duration_day;
            $orderItem->quantity=$request->quantity;
            $orderItem->price=$request->price;
            //$orderItem->discount=$request->grand_discount;
            //$orderItem->per_day_price=$request->per_day_price;
            //$orderItem->total=$request->sub_total;
            $orderItem->note=$request->note;
            $orderItem->type = 'Vendor';
            $orderItem->save();

            if($request->payment_type_id == 1){
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Vehicle Vendor Rent';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->vendor_id;
                $payment->payment_type_id = 1;
                $payment->paid = $request->grand_total;
                $payment->exchange = 0;
                $payment->save();
            }else{
                // paid
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Vehicle Vendor Rent';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->vendor_id;
                $payment->payment_type_id = 1;
                $payment->paid = $request->paid;
                $payment->exchange = 0;
                $payment->save();

                // due
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Vehicle Vendor Rent';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->vendor_id;
                $payment->payment_type_id = 2;
                $payment->paid = $request->due_price;
                $payment->exchange = 0;
                $payment->save();
            }


            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Vehicle Vendor Rent';
            $accessLog->action_done='Create';
            $accessLog->action_remarks='Vehicle Vendor Rent ID: '.$insert_id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Vehicle Vendor Rent Created Successfully');
        return redirect()->route('admin.vehicle-vendor-rent-list');
    }

    public function vehicle_vendor_rent_show($id)
    {
        $vehicleVendorRent = Order::where('order_type','Purchases')->where('id',$id)->first();
        $vehicleVendorRentDetail = OrderItem::where('order_id',$id)->first();
        return view('backend.admin.orders.vehicle_vendor_rent_show',compact('vehicleVendorRent','vehicleVendorRentDetail'));
    }

    public function vehicle_vendor_rent_edit($id)
    {
        $vehicles = Vehicle::where('status',1)->where('own_vehicle_status','Rent')->get();
        $vendors = Vendor::where('status',1)->get();
        $payment_types = PaymentType::all();
        $vehicleVendorRent = Order::where('order_type','Purchases')->where('id',$id)->first();
        $vehicleVendorRentDetail = OrderItem::where('order_id',$id)->first();
        return view('backend.admin.orders.vehicle_vendor_rent_edit',compact('vehicleVendorRent','vehicleVendorRentDetail','vendors','vehicles','payment_types'));
    }

    public function vehicle_vendor_rent_update(Request $request, $id)
    {
        //dd($request->all());
        //dd($id);
        $this->validate($request, [
            //'name'=> 'required|unique:vehicles,name,'.$id,
        ]);

        //if($request->rent_type == 'Daily'){
        $start_year = date('Y', strtotime($request->start_date));
        $start_month = date('m', strtotime($request->start_date));
        $start_day = date('d', strtotime($request->start_date));

        $end_year = date('Y', strtotime($request->end_date));
        $end_month = date('m', strtotime($request->end_date));
        $end_day = date('d', strtotime($request->end_date));



        $date1 = $request->start_date;
        $date2 = $request->end_date;

        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

        // Robeul CUSTOM  ADD 1
        $diff ++;
        //}

        $vehicle = Vehicle::where('id',$request->vehicle_id)->first();
        $vehicleDriver = VehicleDriverAssign::where('vehicle_id',$request->vehicle_id)->first();
        $due_amount = $request->payment_type_id == 1 ? 0 :$request->grand_total;
        //$paid_amount = $request->payment_type_id == 2 ? 0 :$request->grand_total;

        $order = Order::find($id);
        $prev_payment_type_id = $order->payment_type_id;
        $order->vendor_id = $request->vendor_id;
        $order->payment_type_id = $request->payment_type_id;
        $order->sub_total = $request->sub_total;
        $order->discount_type = $request->discount_type;
        $order->discount_percent = $request->discount_percent;
        $order->discount_amount = $request->discount_amount;
        $order->grand_total = $request->grand_total;
        $order->paid = $request->payment_type_id == 1 ? $request->grand_total : $request->paid;
        $order->exchange = 0;
        $order->due_price = $request->payment_type_id == 1 ? 0 : $request->due_price;
        $updated_row = $order->save();

        if($updated_row){
            $orderItem = OrderItem::where('order_id',$id)->first();
            $orderItem->vehicle_id=$request->vehicle_id;
            //$orderItem->driver_id=$vehicleDriver->driver_id;
            $orderItem->rent_type=$request->rent_type;
            $orderItem->start_year=$start_year;
            $orderItem->end_year=$end_year;
            $orderItem->start_month=$start_month;
            $orderItem->end_month=$end_month;
            $orderItem->start_date=$request->start_date;
            $orderItem->end_date=$request->end_date;
            $orderItem->rent_duration_month=$diff;
            $orderItem->rent_duration_day=$request->rent_duration_day;
            $orderItem->quantity=$request->quantity;
            $orderItem->price=$request->price;
            //$orderItem->discount=$request->grand_discount;
            //$orderItem->total=$request->sub_total;
            $orderItem->note=$request->note;
            $orderItem->save();



            if( ($prev_payment_type_id == 1) && ($request->payment_type_id == 1) ){
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->grand_total;
                $payment->save();
            }elseif(($prev_payment_type_id == 1) && ($request->payment_type_id == 2)){
                // paid
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->paid;
                $payment->save();

                // due
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Vehicle Vendor Rent';
                $payment->order_id=$id;
                $payment->paid_user_id=$request->vendor_id;
                $payment->payment_type_id = 2;
                $payment->paid = $request->due_price;
                $payment->exchange = 0;
                $payment->save();
            }elseif(($prev_payment_type_id == 2) && ($request->payment_type_id == 2)){
                // paid
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->paid;
                $payment->save();

                // due
                $payment = Payment::where('order_id',$id)->where('payment_type_id',2)->first();
                $payment->paid = $request->due_price;
                $payment->save();
            }elseif(($prev_payment_type_id == 2) && ($request->payment_type_id == 1)){
                // due
                Payment::where('order_id',$id)->where('payment_type_id',2)->delete();

                // paid
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->grand_total;
                $payment->save();


            }else{
                Toastr::success('Something went wrong!','Success');
                return back();
            }

            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Vehicle Vendor Rent';
            $accessLog->action_done='Update';
            $accessLog->action_remarks='Vehicle Vendor Rent ID: '.$id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Vehicle Vendor Rent updated successfully','Success');
        return redirect()->route('admin.vehicle-vendor-rent-list');
    }

//    public function destroy($id)
//    {
//        $vehicleDriverAssign = VehicleDriverAssign::find($id);
//        $deleted_row = $vehicleDriverAssign->delete();
//        if($deleted_row){
//            $accessLog = new AccessLog();
//            $accessLog->user_id=Auth::user()->id;
//            $accessLog->action_module='Vehicle Driver Assign';
//            $accessLog->action_done='Delete';
//            $accessLog->action_remarks='Vehicle Driver Assign ID: '.$id;
//            $accessLog->action_date=date('Y-m-d');
//            $accessLog->save();
//        }
//
//        Toastr::success('Vehicle Driver Assign deleted successfully','Success');
//        return back();
//    }

    public function supplier_due()
    {
        $vehicleVendorRents = Order::where('type','Vendor')->where('due_price','>',0)->get();
        $payment_types = PaymentType::where('name','!=','Credit')->get();
        return view('backend.admin.orders.vehicle_vendor_rent_due', compact('vehicleVendorRents','payment_types'));
    }
    public function vendorPayDue(Request $request){
        //dd($request->all());

        // update due
        $order = Order::find($request->order_id);
        $last_due_price = $order->due_price - $request->new_paid;
        $order->due_price=$last_due_price;
        $order->save();

        // delete previous due
        Payment::where('order_id',$request->order_id)->where('transaction_type','Vehicle Vendor Rent')->where('payment_type_id',2)->delete();

        // new due paid
        $payment = new Payment();
        $payment->date=date('Y-m-d');
        $payment->transaction_type='Vehicle Vendor Rent';
        $payment->order_id=$request->order_id;
        $payment->paid_user_id=$order->vendor_id;
        $payment->payment_type_id = $request->payment_type_id;
        $payment->paid = $request->new_paid;
        $payment->save();

        // current due
        $payment = new Payment();
        $payment->date=date('Y-m-d');
        $payment->transaction_type='Vehicle Vendor Rent';
        $payment->order_id=$request->order_id;
        $payment->paid_user_id=$order->vendor_id;
        $payment->payment_type_id = 2;
        $payment->paid = $last_due_price;
        $payment->save();

        $accessLog = new AccessLog();
        $accessLog->user_id=Auth::user()->id;
        $accessLog->action_module='Vehicle Vendor Rent';
        $accessLog->action_done='Due Update';
        $accessLog->action_remarks='Vehicle Vendor Rent Order ID: '.$request->order_id;
        $accessLog->action_date=date('Y-m-d');
        $accessLog->save();

        Toastr::success('Vehicle Vendor Rent Order Due Paid Successfully');
        return back();
    }

    public function customerPayDue(Request $request){
        //dd($request->all());

        // update due
        $order = Order::find($request->order_id);
        $last_due_price = $order->due_price - $request->new_paid;
        $order->due_price=$last_due_price;
        $order->save();

        // delete previous due
        Payment::where('order_id',$request->order_id)->where('transaction_type','Vehicle Customer Rent')->where('payment_type_id',2)->delete();

        // new due paid
        $payment = new Payment();
        $payment->date=date('Y-m-d');
        $payment->transaction_type='Vehicle Customer Rent';
        $payment->order_id=$request->order_id;
        $payment->paid_user_id=$order->customer_id;
        $payment->payment_type_id = $request->payment_type_id;
        $payment->paid = $request->new_paid;
        $payment->save();

        // current due
        $payment = new Payment();
        $payment->date=date('Y-m-d');
        $payment->transaction_type='Vehicle Customer Rent';
        $payment->order_id=$request->order_id;
        $payment->paid_user_id=$order->customer_id;
        $payment->payment_type_id = 2;
        $payment->paid = $last_due_price;
        $payment->save();

        $accessLog = new AccessLog();
        $accessLog->user_id=Auth::user()->id;
        $accessLog->action_module='Vehicle Customer Rent';
        $accessLog->action_done='Due Update';
        $accessLog->action_remarks='Vehicle Customer Rent Order ID: '.$request->order_id;
        $accessLog->action_date=date('Y-m-d');
        $accessLog->save();

        Toastr::success('Vehicle Customer Rent Order Due Paid Successfully');
        return back();
    }

    public function order_vendor_print($id){
        $vehicleVendorRent = Order::where('order_type','Purchases')->where('id',$id)->first();
        $vehicleVendorRentDetail = OrderItem::where('order_id',$id)->first();
        $vendor = Vendor::find($vehicleVendorRent->vendor_id);
        $digit = new NumberFormatter("en", NumberFormatter::SPELLOUT);

        return view('backend.admin.orders.invoice_vendor',compact('vehicleVendorRent','vehicleVendorRentDetail','vendor','digit'));
    }

    // customer
    public function vehicle_customer_rent_list()
    {
        $vehicleCustomerRents = Order::where('type','Customer')->get();
        $payment_types = PaymentType::where('name','!=','Credit')->get();
        return view('backend.admin.orders.vehicle_customer_rent_list', compact('vehicleCustomerRents','payment_types'));
    }

    public function vehicle_customer_rent_create()
    {
        //$vehicles = Vehicle::where('status',1)->where('own_vehicle_status','Own')->get();
        $vehicles = Vehicle::where('status',1)->get();
        $customers = Customer::where('status',1)->get();
        $payment_types = PaymentType::all();
        return view('backend.admin.orders.vehicle_customer_rent_create', compact('vehicles','customers','payment_types'));
    }

    public function vehicle_customer_rent_store(Request $request)
    {
        //dd($request->all());
        $this->validate($request, [
            //'name'=> 'required|unique:vehicles,name',
        ]);


        //if($request->rent_type == 'Daily'){
        $start_year = date('Y', strtotime($request->start_date));
        $start_month = date('m', strtotime($request->start_date));
        $start_day = date('d', strtotime($request->start_date));

        $end_year = date('Y', strtotime($request->end_date));
        $end_month = date('m', strtotime($request->end_date));
        $end_day = date('d', strtotime($request->end_date));



        $date1 = $request->start_date;
        $date2 = $request->end_date;

        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

        // Robeul CUSTOM  ADD 1
        $diff ++;
        //}

        $date = date('Y-m-d');
        $datetime = date('Y-m-d H:i:s');
        $vehicle = Vehicle::where('id',$request->vehicle_id)->first();
        $vehicleDriver = VehicleDriverAssign::where('vehicle_id',$request->vehicle_id)->first();

        // invoice no
        if($request->payment_type_id == 1){
            $get_invoice_no = Order::latest()->pluck('invoice_no')->where('payment_type_id',1)->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("CS-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1;
            }
            $final_invoice = 'CS-'.$invoice_no;
        }else{
            $get_invoice_no = Order::latest()->pluck('invoice_no')->where('payment_type_id',2)->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("CR-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1;
            }
            $final_invoice = 'CR-'.$invoice_no;
        }

        $order = new Order();
        $order->date = $date;
        $order->invoice_no = $final_invoice;
        $order->order_type = 'Sales';
        $order->customer_id = $request->customer_id;
        $order->type = 'Customer';
        $order->payment_type_id = $request->payment_type_id;
        $order->sub_total = $request->sub_total;
        $order->discount_type = $request->discount_type;
        $order->discount_percent = $request->discount_percent;
        $order->discount_amount = $request->discount_amount;
        $order->grand_total = $request->grand_total;
        $order->paid = $request->payment_type_id == 1 ? $request->grand_total : $request->paid;
        $order->exchange = 0;
        $order->due_price = $request->payment_type_id == 1 ? 0 : $request->due_price;
        $order->status = 'Done';
        $order->save();

        $insert_id = $order->id;
        if($insert_id){
            $orderItem = new OrderItem();
            $orderItem->date = $date;
            $orderItem->order_id=$insert_id;
            $orderItem->vehicle_id=$request->vehicle_id;
            //$orderItem->driver_id=$vehicleDriver->driver_id;
            $orderItem->rent_type=$request->rent_type;
            $orderItem->start_year=$start_year;
            $orderItem->end_year=$end_year;
            $orderItem->start_month=$start_month;
            $orderItem->end_month=$end_month;
            $orderItem->start_date=$request->start_date;
            $orderItem->end_date=$request->end_date;
            $orderItem->rent_duration_month=$diff;
            $orderItem->rent_duration_day=$request->rent_duration_day;
            $orderItem->quantity=$request->quantity;
            $orderItem->price=$request->price;
            //$orderItem->discount=$request->grand_discount;
            //$orderItem->per_day_price=$request->per_day_price;
            //$orderItem->total=$request->sub_total;
            $orderItem->note=$request->note;
            $orderItem->type = 'Customer';
            $orderItem->save();

            if($request->payment_type_id == 1){
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Vehicle Customer Rent';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->customer_id;
                $payment->payment_type_id = 1;
                $payment->paid = $request->grand_total;
                $payment->exchange = 0;
                $payment->save();
            }else{
                // paid
                $payment = new Payment();
                $payment->date=$date;
                $payment->transaction_type='Vehicle Customer Rent';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->customer_id;
                $payment->payment_type_id = 1;
                $payment->paid = $request->paid;
                $payment->exchange = 0;
                $payment->save();

                // due
                $payment = new Payment();
                $payment->date=$date;
                $payment->transaction_type='Vehicle Customer Rent';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->customer_id;
                $payment->payment_type_id = 2;
                $payment->paid = $request->due_price;
                $payment->exchange = 0;
                $payment->save();
            }


            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Vehicle Customer Rent';
            $accessLog->action_done='Create';
            $accessLog->action_remarks='Vehicle Customer Rent ID: '.$insert_id;
            $accessLog->action_date=$date;
            $accessLog->save();
        }

        Toastr::success('Vehicle Customer Rent Created Successfully');
        return redirect()->route('admin.vehicle-customer-rent-list');
    }

    public function vehicle_customer_rent_show($id)
    {
        $vehicleCustomerRent = Order::where('order_type','Sales')->where('id',$id)->first();
        $vehicleCustomerRentDetail = OrderItem::where('order_id',$id)->first();
        return view('backend.admin.orders.vehicle_customer_rent_show',compact('vehicleCustomerRent','vehicleCustomerRentDetail'));
    }

    public function vehicle_customer_rent_edit($id)
    {
        //$vehicles = Vehicle::where('status',1)->where('own_vehicle_status','Own')->get();
        $vehicles = Vehicle::where('status',1)->get();
        $customers = Customer::where('status',1)->get();
        $payment_types = PaymentType::all();
        $vehicleCustomerRent = Order::where('order_type','Sales')->where('id',$id)->first();
        $vehicleCustomerRentDetail = OrderItem::where('order_id',$id)->first();
        //dd($vehicleCustomerRentDetail);
        return view('backend.admin.orders.vehicle_customer_rent_edit',compact('vehicleCustomerRent','vehicleCustomerRentDetail','customers','vehicles','payment_types'));
    }

    public function vehicle_customer_rent_update(Request $request, $id)
    {
        //dd($request->all());
        //dd($id);
        $this->validate($request, [
            //'name'=> 'required|unique:vehicles,name,'.$id,
        ]);


        //if($request->rent_type == 'Daily'){
        $start_year = date('Y', strtotime($request->start_date));
        $start_month = date('m', strtotime($request->start_date));
        $start_day = date('d', strtotime($request->start_date));

        $end_year = date('Y', strtotime($request->end_date));
        $end_month = date('m', strtotime($request->end_date));
        $end_day = date('d', strtotime($request->end_date));



        $date1 = $request->start_date;
        $date2 = $request->end_date;

        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

        // Robeul CUSTOM  ADD 1
        $diff ++;
        //}

        $date = date('Y-m-d');

        $vehicle = Vehicle::where('id',$request->vehicle_id)->first();
        $vehicleDriver = VehicleDriverAssign::where('vehicle_id',$request->vehicle_id)->first();
        $due_amount = $request->payment_type_id == 1 ? 0 :$request->grand_total;
        //$paid_amount = $request->payment_type_id == 2 ? 0 :$request->grand_total;

        $order = Order::find($id);
        $prev_payment_type_id = $order->payment_type_id;
        $order->customer_id = $request->customer_id;
        $order->payment_type_id = $request->payment_type_id;
        $order->discount_type = $request->discount_type;
        $order->discount_percent = $request->discount_percent;
        $order->discount_amount = $request->discount_amount;
        $order->sub_total = $request->sub_total;
        $order->grand_total = $request->grand_total;
        $order->paid = $request->payment_type_id == 1 ? $request->grand_total : $request->paid;
        $order->exchange = 0;
        $order->due_price = $request->payment_type_id == 1 ? 0 : $request->due_price;
        $updated_row = $order->save();

        if($updated_row){
            $orderItem = OrderItem::where('order_id',$id)->first();
            $orderItem->vehicle_id=$request->vehicle_id;
            //$orderItem->driver_id=$vehicleDriver->driver_id;
            $orderItem->rent_type=$request->rent_type;
            $orderItem->start_year=$start_year;
            $orderItem->end_year=$end_year;
            $orderItem->start_month=$start_month;
            $orderItem->end_month=$end_month;
            $orderItem->start_date=$request->start_date;
            $orderItem->end_date=$request->end_date;
            $orderItem->rent_duration_month=$diff;
            $orderItem->rent_duration_day=$request->rent_duration_day;
            $orderItem->quantity=$request->quantity;
            $orderItem->price=$request->price;
            //$orderItem->discount=$request->grand_discount;
            //$orderItem->total=$request->sub_total;
            $orderItem->note=$request->note;
            $orderItem->save();



            if( ($prev_payment_type_id == 1) && ($request->payment_type_id == 1) ){
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->grand_total;
                $payment->save();
            }elseif(($prev_payment_type_id == 1) && ($request->payment_type_id == 2)){
                // paid
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->paid;
                $payment->save();

                // due
                $payment = new Payment();
                $payment->date=$date;
                $payment->transaction_type='Vehicle Customer Rent';
                $payment->order_id=$id;
                $payment->paid_user_id=$request->customer_id;
                $payment->payment_type_id = 2;
                $payment->paid = $request->due_price;
                $payment->exchange = 0;
                $payment->save();
            }elseif(($prev_payment_type_id == 2) && ($request->payment_type_id == 2)){
                // paid
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->paid;
                $payment->save();

                // due
                $payment = Payment::where('order_id',$id)->where('payment_type_id',2)->first();
                $payment->paid = $request->due_price;
                $payment->save();
            }elseif(($prev_payment_type_id == 2) && ($request->payment_type_id == 1)){
                // due
                Payment::where('order_id',$id)->where('payment_type_id',2)->delete();

                // paid
                $payment = Payment::where('order_id',$id)->where('payment_type_id',1)->first();
                $payment->paid = $request->grand_total;
                $payment->save();


            }else{
                Toastr::success('Something went wrong!','Success');
                return back();
            }

            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Vehicle Customer Rent';
            $accessLog->action_done='Update';
            $accessLog->action_remarks='Vehicle Customer Rent ID: '.$id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Vehicle Customer Rent updated successfully','Success');
        return redirect()->route('admin.vehicle-customer-rent-list');
    }

    public function order_Customer_print($id){
        $vehicleCustomerRent = Order::where('order_type','Sales')->where('id',$id)->first();
        $vehicleCustomerRentDetail = OrderItem::where('order_id',$id)->first();
        $customer = Customer::find($vehicleCustomerRent->customer_id);
        $digit = new NumberFormatter("en", NumberFormatter::SPELLOUT);

        return view('backend.admin.orders.invoice_customer',compact('vehicleCustomerRent','vehicleCustomerRentDetail','customer','digit'));
    }

    public function customer_due()
    {
        $vehicleCustomerRents = Order::where('type','Customer')->where('due_price','>',0)->get();
        $payment_types = PaymentType::where('name','!=','Credit')->get();
        return view('backend.admin.orders.vehicle_customer_rent_due', compact('vehicleCustomerRents','payment_types'));
    }
}
