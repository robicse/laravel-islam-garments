<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ExpenseCategory;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExpenseCategoryController extends Controller
{
    // Expense Category
    public function expenseCategoryList(){
        try {
            $expense_categories = DB::table('expense_categories')->select('id','name','status')->orderBy('id','desc')->get();

            if($expense_categories === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Expense Category Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$expense_categories);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    // Expense Category
    public function expenseCategoryActiveList(){
        try {
            $expense_categories = DB::table('expense_categories')->select('id','name','status')->where('status',1)->orderBy('id','desc')->get();

            if($expense_categories === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Expense Category Found.',null);
                return response()->json($response,404);
            }else{
                $response = APIHelpers::createAPIResponse(false,200,'',$expense_categories);
                return response()->json($response,200);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function expenseCategoryCreate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:expense_categories,name',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }


            $expenseCategory = new ExpenseCategory();
            $expenseCategory->name = $request->name;
            $expenseCategory->status = $request->status;
            $expenseCategory->save();
            $insert_id = $expenseCategory->id;

            if($insert_id){
                $user_id = Auth::user()->id;
                // parent head info
                $account = DB::table('chart_of_accounts')
                    ->where('head_level',2)
                    ->where('head_code', 'like', '402%')
                    ->Orderby('created_at', 'desc')
                    ->limit(1)
                    ->first();

                if(!empty($account)){
                    $head_code=$account->head_code+1;
                }else{
                    $head_code="40201";
                }


                $chart_of_accounts = new ChartOfAccount();
                $chart_of_accounts->head_code = $head_code;
                $chart_of_accounts->head_name = $request->name;
                $chart_of_accounts->parent_head_name = 'Office Expenses';
                $chart_of_accounts->head_type = 'E';
                $chart_of_accounts->head_level = 2;
                $chart_of_accounts->is_active = 1;
                $chart_of_accounts->is_transaction = 1;
                $chart_of_accounts->is_general_ledger = 1;
                $chart_of_accounts->ref_id = $insert_id;
                $chart_of_accounts->user_bank_account_no = NULL;
                $chart_of_accounts->created_by = $user_id;
                $chart_of_accounts->updated_by = $user_id;
                $chart_of_accounts->save();
            }

            $response = APIHelpers::createAPIResponse(false,201,'Expense Category Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function expenseCategoryEdit(Request $request){
//        try {
            $validator = Validator::make($request->all(), [
                'expense_category_id'=> 'required',
                'name' => 'required|unique:expense_categories,name,'.$request->expense_category_id,
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $check_exists_expense_category = DB::table("expense_categories")->where('id',$request->expense_category_id)->pluck('id')->first();
            if($check_exists_expense_category == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Product Category Found.',null);
                return response()->json($response,404);
            }

            $expense_category = ExpenseCategory::find($request->expense_category_id);
            $expense_category->name = $request->name;
            $expense_category->status = $request->status;
            $update_expense_category = $expense_category->save();

            if($update_expense_category){
                $account = ChartOfAccount::where('ref_id',$request->expense_category_id)
                    ->where('head_type',  'E')
                    ->where('parent_head_name',  'Office Expenses')
                    ->first();
                if(!empty($account)){
                    $account->head_name=$request->name;
                    $account->save();
                }

                $response = APIHelpers::createAPIResponse(false,200,'Expense Category Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Expense Category Updated Failed.',null);
                return response()->json($response,400);
            }
//        } catch (\Exception $e) {
//            //return $e->getMessage();
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }

    public function expenseCategoryDelete(Request $request){
        try {
            $check_exists_expense_category = DB::table("expense_categories")->where('id',$request->expense_category_id)->pluck('id')->first();
            if($check_exists_expense_category == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Expense Category Found.',null);
                return response()->json($response,404);
            }

            //$delete_party = DB::table("expense_categories")->where('id',$request->expense_category_id)->delete();
            $soft_delete_expense_category = ExpenseCategory::find($request->expense_category_id);
            $soft_delete_expense_category->status=0;
            $affected_row = $soft_delete_expense_category->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Expense Category Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Expense Category Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
