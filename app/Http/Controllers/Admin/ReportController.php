<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Model\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:report-payment-list', ['only' => ['reportPayment']]);
//        $this->middleware('permission:vendor-balance-sheet', ['only' => ['vendor_balance_sheet_form']]);
//        $this->middleware('permission:customer-payment-list', ['only' => ['customer_balance_sheet_form']]);
//        $this->middleware('permission:driver-payment-list', ['only' => ['driver_balance_sheet_form']]);
//        $this->middleware('permission:staff-payment-list', ['only' => ['staff_balance_sheet_form']]);
//        //$this->middleware('permission:vehicle-payment-list', ['only' => ['vendor_balance_sheet_form']]);
//    }

    public function reportPayment(Request $request)
    {
        if($request->date_from)
        {
            $date_from = $request->date_from;
        }else{
            $date_from = '2021-12-01';
        }
        if($request->date_to)
        {
            $date_to = $request->date_to;
        }else{
            $date_to = date('Y-m-d');
        }

        $payments = Payment::whereBetween('date', [$date_from, $date_to])->latest()->get();

        return view('backend.admin.reports.payment', compact('payments','date_from','date_to'));
    }

    public function report_payment_print($date_from,$date_to)
    {
        if( (!empty($date_from)) && (!empty($date_to)) )
        {
            $payments = Payment::whereBetween('date', [$date_from, $date_to])->latest()->get();
        }else{
            $payments = Payment::latest()->get();
        }

        return view('backend.admin.reports.payment_print', compact('payments','date_from','date_to'));
    }

//    public function vendor_balance_sheet_form(Request $request)
//    {
//        $vendor_previous_total_paid=0;
//        if($request->date_from)
//        {
//            $date_from = $request->date_from;
//        }else{
//            $date_from = '2021-12-01';
//        }
//        if($request->date_to)
//        {
//            $date_to = $request->date_to;
//        }else{
//            $date_to = date('Y-m-d');
//        }
//        $vendor_cash_pre_valance_data = DB::table('payments')
//            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
//            ->where('date', '<',$date_from)
//            ->where('transaction_type','Vehicle Vendor Rent')
//            ->where('payment_type_id',1)
//            ->groupBy('paid_user_id')
//            ->first();
//
//        if(!empty($vendor_cash_pre_valance_data))
//        {
//            $vendor_previous_total_paid = $vendor_cash_pre_valance_data->total_paid;
//        }
//
//        $vendor_cash_data_results = DB::table('payments')
//            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
//            ->where('transaction_type','Vehicle Vendor Rent')
//            ->where('payment_type_id',1)
//            ->whereBetween('date', [$date_from, $date_to])
//            ->groupBy('paid_user_id')
//            ->get();
//
//        return view('backend.admin.reports.vendor_balance_sheet_form', compact('vendor_cash_data_results', 'vendor_previous_total_paid','date_from','date_to'));
//    }
//
//    public function report_vendor_balance_sheet_print($date_from,$date_to)
//    {
//        $vendor_previous_total_paid=0;
//
//        $vendor_cash_pre_valance_data = DB::table('payments')
//            //->select('account_no', DB::raw('SUM(debit) as debit, SUM(credit) as credit'))
//            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
//            ->where('date', '<',$date_from)
//            ->where('transaction_type','Vehicle Vendor Rent')
//            ->where('payment_type_id',1)
//            ->groupBy('paid_user_id')
//            ->first();
//
//        if(!empty($vendor_cash_pre_valance_data))
//        {
//            $vendor_previous_total_paid = $vendor_cash_pre_valance_data->total_paid;
//        }
//
//        if( (!empty($date_from)) && (!empty($date_to)) )
//        {
//            $vendor_cash_data_results = DB::table('payments')
//                ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
//                ->where('transaction_type','Vehicle Vendor Rent')
//                ->where('payment_type_id',1)
//                ->whereBetween('date', [$date_from, $date_to])
//                ->groupBy('paid_user_id')
//                ->get();
//        }else{
//            $vendor_cash_data_results = DB::table('payments')
//                ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
//                ->where('transaction_type','Vehicle Vendor Rent')
//                ->where('payment_type_id',1)
//                ->groupBy('paid_user_id')
//                ->get();
//        }
//
//        return view('backend.admin.reports.vendor_balance_sheet_print', compact('vendor_cash_data_results', 'vendor_previous_total_paid','date_from','date_to'));
//    }

    public function customer_balance_sheet_form(Request $request)
    {
        $customer_previous_total_paid=0;
        if($request->date_from)
        {
            $date_from = $request->date_from;
        }else{
            $date_from = '2021-12-01';
        }
        if($request->date_to)
        {
            $date_to = $request->date_to;
        }else{
            $date_to = date('Y-m-d');
        }

        $customer_cash_pre_valance_data = DB::table('payments')
            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
            ->where('date', '<',$date_from)
            ->where('payment_type_id',1)
            ->where('transaction_type','Vehicle Customer Rent')
            ->groupBy('paid_user_id')
            ->first();

        if(!empty($customer_cash_pre_valance_data))
        {
            $customer_previous_total_paid = $customer_cash_pre_valance_data->total_paid;
        }

        $customer_cash_data_results = DB::table('payments')
            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
            ->where('transaction_type','Vehicle Customer Rent')
            ->where('payment_type_id',1)
            ->whereBetween('date', [$date_from, $date_to])
            ->groupBy('paid_user_id')
            ->get();

        return view('backend.admin.reports.customer_balance_sheet_form', compact('customer_cash_data_results', 'customer_previous_total_paid','date_from','date_to'));
    }

    public function report_customer_balance_sheet_print($date_from,$date_to)
    {
        $customer_previous_total_paid=0;

        $customer_cash_pre_valance_data = DB::table('payments')
            //->select('account_no', DB::raw('SUM(debit) as debit, SUM(credit) as credit'))
            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
            ->where('date', '<',$date_from)
            ->where('payment_type_id',1)
            ->where('transaction_type','Vehicle Customer Rent')
            ->groupBy('paid_user_id')
            ->first();

        if(!empty($customer_cash_pre_valance_data))
        {
            $customer_previous_total_paid = $customer_cash_pre_valance_data->total_paid;
        }

        if( (!empty($date_from)) && (!empty($date_to)) )
        {
            $customer_cash_data_results = DB::table('payments')
                ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
                ->where('transaction_type','Vehicle Customer Rent')
                ->where('payment_type_id',1)
                ->whereBetween('date', [$date_from, $date_to])
                ->groupBy('paid_user_id')
                ->get();
        }else{
            $customer_cash_data_results = DB::table('payments')
                ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
                ->where('transaction_type','Vehicle Customer Rent')
                ->where('payment_type_id',1)
                ->groupBy('paid_user_id')
                ->get();
        }

        return view('backend.admin.reports.customer_balance_sheet_print', compact('customer_cash_data_results', 'customer_previous_total_paid','date_from','date_to'));
    }

    public function staff_balance_sheet_form(Request $request)
    {
        $staff_previous_total_paid=0;
        if($request->date_from)
        {
            $date_from = $request->date_from;
        }else{
            $date_from = '2021-12-01';
        }
        if($request->date_to)
        {
            $date_to = $request->date_to;
        }else{
            $date_to = date('Y-m-d');
        }

        $staff_cash_pre_valance_data = DB::table('payments')
            //->select('account_no', DB::raw('SUM(debit) as debit, SUM(credit) as credit'))
            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
            ->where('date', '<',$date_from)
            ->where('payment_type_id',1)
            ->where('transaction_type','Staff Salary')
            ->groupBy('paid_user_id')
            ->first();

        if(!empty($staff_cash_pre_valance_data))
        {
            $staff_previous_total_paid = $staff_cash_pre_valance_data->total_paid;
        }

        $staff_cash_data_results = DB::table('payments')
            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
            ->where('transaction_type','Staff Salary')
            ->where('payment_type_id',1)
            ->whereBetween('date', [$date_from, $date_to])
            ->groupBy('paid_user_id')
            ->get();

        return view('backend.admin.reports.staff_balance_sheet_form', compact('staff_cash_data_results', 'staff_previous_total_paid','date_from','date_to'));
    }

    public function report_staff_balance_sheet_print($date_from,$date_to)
    {
        $staff_previous_total_paid=0;

        $staff_cash_pre_valance_data = DB::table('payments')
            //->select('account_no', DB::raw('SUM(debit) as debit, SUM(credit) as credit'))
            ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
            ->where('date', '<',$date_from)
            ->where('payment_type_id',1)
            ->where('transaction_type','Staff Salary')
            ->groupBy('paid_user_id')
            ->first();

        if(!empty($staff_cash_pre_valance_data))
        {
            $staff_previous_total_paid = $staff_cash_pre_valance_data->total_paid;
        }

        if( (!empty($date_from)) && (!empty($date_to)) )
        {
            $staff_cash_data_results = DB::table('payments')
                ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
                ->where('transaction_type','Staff Salary')
                ->where('payment_type_id',1)
                ->whereBetween('date', [$date_from, $date_to])
                ->groupBy('paid_user_id')
                ->get();
        }else{
            $staff_cash_data_results = DB::table('payments')
                ->select('paid_user_id', DB::raw('SUM(paid) as total_paid'))
                ->where('transaction_type','Staff Salary')
                ->where('payment_type_id',1)
                ->groupBy('paid_user_id')
                ->get();
        }

        return view('backend.admin.reports.staff_balance_sheet_print', compact('staff_cash_data_results', 'staff_previous_total_paid','date_from','date_to'));
    }

    public function loss_profit(Request $request){
        if($request->date_from)
        {
            $date_from = $request->date_from;
        }else{
            $date_from = '2021-12-01';
        }
        if($request->date_to)
        {
            $date_to = $request->date_to;
        }else{
            $date_to = date('Y-m-d');
        }

        return view('backend.admin.reports.loss_profit', compact('date_from', 'date_to'));
    }

    public function loss_profit_print(Request $request){
        $date_from = '';
        $date_to = '';

        if($request->date_from)
        {
            $date_from = $request->date_from;
        }
        if($request->date_to)
        {
            $date_to = $request->date_to;
        }

        return view('backend.admin.reports.loss_profit_print', compact('date_from', 'date_to'));
    }
}
