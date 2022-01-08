<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\AccessLog;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\DriverSalary;
use App\Model\Order;
use App\Model\OrderItem;
use App\Model\Payment;
use App\Model\PaymentType;
use App\Model\StaffSalary;
use App\Model\Vehicle;
use App\Model\VehicleDriverAssign;
use App\Model\Vendor;
use App\User;
use NumberFormatter;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class StaffSalaryController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:staff-salary-list|staff-salary-create|staff-salary-edit|staff-salary-delete|staff_salary_show', ['only' => ['staff_salary_list','staff_salary_store']]);
        $this->middleware('permission:staff-salary-create', ['only' => ['staff_salary_create','staff_salary_store']]);
        $this->middleware('permission:staff-salary-edit', ['only' => ['staff_salary_edit','staff_salary_update']]);
        $this->middleware('permission:staff-salary-delete', ['only' => ['staff_salary_destroy']]);
    }

    public function staff_salary_list()
    {
        $staffSalaries = StaffSalary::latest()->get();
        $payment_types = PaymentType::all();
        return view('backend.admin.staff_salaries.staff_salary_list', compact('staffSalaries','payment_types'));
    }

    public function staff_salary_create()
    {
        $staffs = User::where('status',1)->where('user_type','staff')->get();
        $payment_types = PaymentType::all();
        return view('backend.admin.staff_salaries.staff_salary_create', compact('staffs','payment_types'));
    }

    public function staff_salary_store(Request $request)
    {
        //dd($request->all());
        $this->validate($request, [
            //'name'=> 'required|unique:vehicles,name',
        ]);

        $date = date('Y-m-d H:i:s');

        // invoice no
        if($request->payment_type_id == 1){
            $get_invoice_no = StaffSalary::latest()->pluck('invoice_no')->where('payment_type_id',1)->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("CSSS-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1;
            }
            $final_invoice = 'CSSS-'.$invoice_no;
        }else{
            $get_invoice_no = StaffSalary::latest()->pluck('invoice_no')->where('payment_type_id',2)->first();
            if(!empty($get_invoice_no)){
                $get_invoice = str_replace("CRSS-","",$get_invoice_no);
                $invoice_no = $get_invoice+1;
            }else{
                $invoice_no = 1;
            }
            $final_invoice = 'CRSS-'.$invoice_no;
        }
        // invoice no

        $staffSalary = new StaffSalary();
        $staffSalary->date = $date;
        $staffSalary->invoice_no = $final_invoice;
        $staffSalary->user_id = $request->user_id;
        $staffSalary->year = $request->year;
        $staffSalary->month = $request->month;
        $staffSalary->salary = $request->salary;
        $staffSalary->payment_type_id = $request->payment_type_id;
        $staffSalary->paid = $request->paid;
        $staffSalary->due = $request->due_price;
        $staffSalary->note = $request->note;
        $staffSalary->save();
        $insert_id = $staffSalary->id;
        if($insert_id){

            if($request->payment_type_id == 1){
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Staff Salary';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->user_id;
                $payment->payment_type_id = 1;
                $payment->paid = $request->paid;
                $payment->exchange = 0;
                $payment->save();
            }else{
                // paid
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Staff Salary';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->user_id;
                $payment->payment_type_id = 1;
                $payment->paid = $request->paid;
                $payment->exchange = 0;
                $payment->save();

                // due
                $payment = new Payment();
                $payment->date=date('Y-m-d');
                $payment->transaction_type='Staff Salary';
                $payment->order_id=$insert_id;
                $payment->paid_user_id=$request->user_id;
                $payment->payment_type_id = 2;
                $payment->paid = $request->due_price;
                $payment->exchange = 0;
                $payment->save();
            }


            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Staff Salary';
            $accessLog->action_done='Create';
            $accessLog->action_remarks='Staff Salary ID: '.$insert_id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }

        Toastr::success('Staff Salary Created Successfully');
        return redirect()->route('admin.staff-salary-list');
    }

    public function Staff_salary_show($id)
    {
        $staffSalary = StaffSalary::where('id',$id)->first();
        $staffs = User::where('status',1)->where('user_type','staff')->get();
        return view('backend.admin.staff_salaries.staff_salary_show',compact('staffSalary','staffs'));
    }

    public function staff_salary_edit($id)
    {
        $staffSalary = StaffSalary::find($id);
        $staffs = User::where('status',1)->where('user_type','staff')->get();
        $payment_types = PaymentType::all();
        return view('backend.admin.driver_salaries.staff_salary_edit',compact('staffSalary','staffs','payment_types'));
    }

    public function staff_salary_update(Request $request, $id)
    {
        dd($request->all());
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
                $payment->order_id=$id;
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

    public function vehicle_vendor_rent_due()
    {
        $vehicleVendorRents = Order::where('type','Vendor')->where('due_price','>',0)->get();
        $payment_types = PaymentType::where('name','!=','Credit')->get();
        return view('backend.admin.orders.vehicle_vendor_rent_due', compact('vehicleVendorRents','payment_types'));
    }
    public function driverPayDue(Request $request){
        //dd($request->all());

        // update due
        $driverSalary = DriverSalary::find($request->order_id);
        $last_due_price = $driverSalary->due - $request->new_paid;
        $last_paid = $driverSalary->paid + $request->new_paid;
        $driverSalary->paid=$last_paid;
        $driverSalary->due=$last_due_price;
        $driverSalary->save();

        // delete previous due
        Payment::where('order_id',$request->order_id)->where('transaction_type','Driver Salary')->where('payment_type_id',2)->delete();

        // new due paid
        $payment = new Payment();
        $payment->date=date('Y-m-d');
        $payment->transaction_type='Driver Salary';
        $payment->order_id=$request->order_id;
        $payment->payment_type_id = $request->payment_type_id;
        $payment->paid = $request->new_paid;
        $payment->save();

        // current due
        $payment = new Payment();
        $payment->date=date('Y-m-d');
        $payment->transaction_type='Driver Salary';
        $payment->order_id=$request->order_id;
        $payment->payment_type_id = 2;
        $payment->paid = $last_due_price;
        $payment->save();

        $accessLog = new AccessLog();
        $accessLog->user_id=Auth::user()->id;
        $accessLog->action_module='Driver Salary';
        $accessLog->action_done='Due Update';
        $accessLog->action_remarks='Driver Salary Order ID: '.$request->order_id;
        $accessLog->action_date=date('Y-m-d');
        $accessLog->save();

        Toastr::success('Driver Salary Due Paid Successfully');
        return back();
    }

    public function order_vendor_print($id){
        $vehicleVendorRent = Order::where('order_type','Purchases')->where('id',$id)->first();
        $vehicleVendorRentDetail = OrderItem::where('order_id',$id)->first();
        $vendor = Vendor::find($vehicleVendorRent->vendor_id);
        $digit = new NumberFormatter("en", NumberFormatter::SPELLOUT);

        return view('backend.admin.orders.invoice_vendor',compact('vehicleVendorRent','vehicleVendorRentDetail','vendor','digit'));
    }

    public function check_staff_salary(Request $request){
        return User::where('id',$request->user_id)->pluck('salary')->first();
    }

    public function check_already_staff_salary(Request $request){
        $check_exists_salary =  StaffSalary::where('user_id',$request->user_id)
            ->where('year',$request->year)
            ->where('month',$request->month)
            ->pluck('salary')->first();
        if(!empty($check_exists_salary)){
            return 'Found';
        }else{
            return 'Not Found';
        }
    }
}
