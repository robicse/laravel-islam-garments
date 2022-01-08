<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\OverallCost;
use App\Model\OverallCostCategory;
use App\Model\Payment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OverallCostController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:overall-cost-list|overall-cost-create|overall-cost-edit|overall-cost-delete', ['only' => ['index','store']]);
//        $this->middleware('permission:overall-cost-create', ['only' => ['create','store']]);
//        $this->middleware('permission:overall-cost-edit', ['only' => ['edit','update']]);
//        $this->middleware('permission:overall-cost-delete', ['only' => ['destroy']]);
//    }

    public function index()
    {
        $overallCosts = OverallCost::latest()->get();
        return view('backend.admin.overallCost.index', compact('overallCosts'));
    }

    public function create()
    {
        $overallCostCategories = OverallCostCategory::all();
        return view('backend.admin.overallCost.create', compact('overallCostCategories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'overall_cost_category_id'=> 'required',
            'payment_type'=> 'required',
            'amount'=> 'required',
            'date'=> 'required',
        ]);

        $overallCost = new OverallCost();
        $overallCost->user_id = Auth::id();
        $overallCost->overall_cost_category_id = $request->overall_cost_category_id;
        $overallCost->payment_type = $request->payment_type;
        $overallCost->cheque_number = $request->cheque_number ? $request->cheque_number : NULL;
        $overallCost->amount = $request->amount;
        $overallCost->date = $request->date;
        $overallCost->save();
        $insert_id = $overallCost->id;

        $payment = new Payment();
        $payment->date=date('Y-m-d');
        $payment->transaction_type='Overall Cost';
        $payment->order_id=$insert_id;
        $payment->paid_user_id=NULL;
        $payment->payment_type_id = 1;
        $payment->paid = $request->amount;
        $payment->exchange = 0;
        $payment->save();

        Toastr::success('Overall Cost Created Successfully');
        return redirect()->route('admin.overall-cost.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $overallCost = OverallCost::find($id);
        $overallCostCategories = OverallCostCategory::all();
        return view('backend.admin.overallCost.edit', compact('overallCost','overallCostCategories'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'overall_cost_category_id'=> 'required',
            'payment_type'=> 'required',
            'amount'=> 'required',
            'date'=> 'required',
        ]);

        $overallCost = OverallCost::find($id);
        $overallCost->overall_cost_category_id = $request->overall_cost_category_id;
        $overallCost->payment_type = $request->payment_type;
        $overallCost->cheque_number = $request->cheque_number ? $request->cheque_number : NULL;
        $overallCost->amount = $request->amount;
        $overallCost->date = $request->date;
        $overallCost->save();

        $payment = Payment::where('order_id',$id)
            ->where('payment_type_id',1)
            ->where('transaction_type','Overall Cost')
            ->first();
        $payment->paid = $request->amount;
        $payment->save();

        Toastr::success('Overall Cost Updated Successfully');
        return redirect()->route('admin.overall-cost.index');
    }

    public function destroy($id)
    {
        //OfficeCostingCategory::destroy($id);
        Toastr::warning('Overall Cost not deleted possible, Please contact with administrator!');
        return redirect()->route('admin.overall-cost.index');
    }
}
