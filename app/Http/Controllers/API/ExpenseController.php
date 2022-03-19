<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Expense;
use App\ExpenseCategory;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\VoucherType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function expenseListPaginationWithSearch(Request $request){
        try {
            $expenses = Expense::join('expense_categories','expenses.expense_category_id','expense_categories.id')
                ->leftJoin('warehouses','expenses.warehouse_id','warehouses.id')
                ->leftJoin('stores','expenses.store_id','stores.id')
                ->where('expenses.amount','like','%'.$request->search.'%')
                ->orWhere('warehouses.name','like','%'.$request->search.'%')
                ->orWhere('stores.name','like','%'.$request->search.'%')
                ->select(
                    'expenses.id as expense_id',
                    'expenses.amount',
                    'expenses.expense_from',
                    'warehouses.id as warehouse_id',
                    'warehouses.name as warehouse_name',
                    'stores.id as store_id',
                    'stores.name as store_name',
                    'expense_categories.id as expense_category_id',
                    'expense_categories.name as expense_category_name',
                    'expenses.date'
                );

            if($request->search){
                $expenses->where('expenses.amount','like','%'.$request->search.'%')
                    ->orWhere('warehouses.name','like','%'.$request->search.'%')
                    ->orWhere('stores.name','like','%'.$request->search.'%');
            }

            $expenses->latest('expenses.id','desc')->paginate(12);

            if(count($expenses) === 0){
                $response = APIHelpers::createAPIResponse(true,404,'No Expenses Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$expenses);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function expenseCreate(Request $request){
        try {
            $expense_category_id = $request->expense_category_id;
            $payment_type_id = $request->payment_type_id;
            $amount = $request->amount;

            // required
            $validator = Validator::make($request->all(), [
                'expense_from' => 'required',
                'expense_category_id' => 'required',
                'amount' => 'required',
                'payment_type_id' => 'required'
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            // posting
            $date = date('Y-m-d');
            $user_id = Auth::user()->id;
            $warehouse_id = $request->warehouse_id ? $request->warehouse_id : NULL;
            $store_id = $request->store_id ? $request->store_id : NULL;
            $month = date('m');
            $year = date('Y');
            $transaction_date_time = date('Y-m-d H:i:s');

            $expense = new Expense();
            $expense->expense_from=$request->expense_from;
            $expense->warehouse_id=$warehouse_id;
            $expense->store_id=$store_id;
            $expense->expense_category_id=$request->expense_category_id;
            $expense->amount=$request->amount;
            $expense->date=date('Y-m-d');
            $expense->save();
            $insert_id = $expense->id;

            if($insert_id){
                if($payment_type_id == 1) {
                    // Cash In Hand For Paid Amount
                    $get_voucher_name = VoucherType::where('id', 9)->pluck('name')->first();
                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id', 9)->latest()->pluck('voucher_no')->first();
                    if (!empty($get_voucher_no)) {
                        $get_voucher_name_str = $get_voucher_name . "-";
                        $get_voucher = str_replace($get_voucher_name_str, "", $get_voucher_no);
                        $voucher_no = $get_voucher + 1;
                    } else {
                        $voucher_no = 9000;
                    }
                    $final_voucher_no = $get_voucher_name . '-' . $voucher_no;

                    // Cash In Hand Account Info
                    $cash_chart_of_account_info = ChartOfAccount::where('head_name', 'Cash In Hand')->first();

                    // expense head
                    $code = ExpenseCategory::where('id', $expense_category_id)->pluck('code')->first();
                    $expense_chart_of_account_info = ChartOfAccount::where('name_code', $code)->first();

                    // Cash In Hand debit
                    $description = 'Cash In Hand Debit For Expense Paid';
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 9, $final_voucher_no, 'Expense Paid', $date, $transaction_date_time, $year, $month, $warehouse_id, $store_id, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, $amount, NULL, $description, 'Approved');

                    // expense credit
                    $description = $expense_chart_of_account_info->head_name . ' Expense Credited For Expense Paid';
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 9, $final_voucher_no, 'Expense Paid', $date, $transaction_date_time, $year, $month, $warehouse_id, $store_id, $payment_type_id, NULL, NULL, NULL, $expense_chart_of_account_info->id, $expense_chart_of_account_info->head_code, $expense_chart_of_account_info->head_name, $expense_chart_of_account_info->parent_head_name, $expense_chart_of_account_info->head_type, NULL, $amount, $description, 'Approved');
                }

                $response = APIHelpers::createAPIResponse(false,200,'Expense Created Successfully.',null);
                return response()->json($response,200);
            }

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function expenseEdit(Request $request){
        try {
            $amount = $request->amount;

            // required
            $validator = Validator::make($request->all(), [
                'expense_id' => 'required',
                'amount' => 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            // posting
            $user_id = Auth::user()->id;
            $expense_id = $request->expense_id;

            $expense = Expense::find($expense_id);
            $expense->amount=$request->amount;
            $affected_row = $expense->save();

            if($affected_row){
                // Cash In Hand For Paid Amount
                $chart_of_account_transaction = ChartOfAccountTransaction::where('ref_id',$expense->id)
                ->where('transaction_type','Expense paid')
                ->first();
                $chart_of_account_transaction->user_id = $user_id;
                $affected_row2 = $chart_of_account_transaction->save();

                if ($affected_row2) {
                    // expense head
                    $code = ExpenseCategory::where('id', $expense->expense_category_id)->pluck('code')->first();

                    // Cash In Hand debit
                    $chart_of_account_transaction_details = ChartOfAccountTransactionDetail::where('chart_of_account_transaction_id',$chart_of_account_transaction->id)
                        ->where('chart_of_account_name','Cash In Hand')
                        ->where('debit','>',0)
                        ->first();
                    if(!empty($chart_of_account_transaction_details)){
                        $chart_of_account_transaction_details->debit = $amount;
                        $chart_of_account_transaction_details->payment_type_id=1;
                        $chart_of_account_transaction_details->save();
                    }

                    // expense credit
                    $chart_of_account_transaction_details = ChartOfAccountTransactionDetail::where('chart_of_account_transaction_id',$chart_of_account_transaction->id)
                        ->where('chart_of_account_name',$code)
                        ->where('credit','>',0)
                        ->first();
                    if(!empty($chart_of_account_transaction_details)){
                        $chart_of_account_transaction_details->credit = $amount;
                        $chart_of_account_transaction_details->payment_type_id=1;
                        $chart_of_account_transaction_details->save();
                    }
                }

                $response = APIHelpers::createAPIResponse(false,200,'Expense Created Successfully.',null);
                return response()->json($response,200);
            }

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
