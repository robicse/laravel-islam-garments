<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Customer;
use App\Helpers\APIHelpers;
use App\Helpers\ImageHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerCollection;
use App\VoucherType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    // posCustomerActiveList
    public function posCustomerActiveList(){
        try {
            $customers = DB::table('customers')
                ->select('id','name','status')
                ->where('status',1)
                ->where('customer_type','POS Sale')
                ->orderBy('id','desc')
                ->get();

            if($customers === null){
                $response = APIHelpers::createAPIResponse(true,404,'No POS Customer Found.',null);
                return response()->json($response,404);
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$customers);
            return response()->json($response,200);

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    // posCustomerActiveList
    public function wholeCustomerActiveList(){
        try {
            $customers = DB::table('customers')
                ->select('id','name','shop_name','phone','status')
                ->where('status',1)
                ->where('customer_type','Whole Sale')
                ->orderBy('id','desc')
                ->get();

            if($customers === null){
                $response = APIHelpers::createAPIResponse(true,404,'No POS Customer Found.',null);
                return response()->json($response,404);
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$customers);
            return response()->json($response,200);

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    // customer
    public function customerList(){
        try {
            $customers = DB::table('customers')
                ->select('id','customer_type','code','shop_name','name','phone','email','address','initial_due','current_total_due','nid_front','nid_back','image','bank_detail_image','note','status')
                ->orderBy('id','desc')
                ->get();

            if($customers === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Customers Found.',null);
                return response()->json($response,404);
            }

            $customer_arr = [];
            foreach ($customers as $customer) {
                $nested_data['id'] = $customer->id;
                $nested_data['shop_name'] = $customer->shop_name;
                $nested_data['customer_type'] = $customer->customer_type;
                $nested_data['code'] = $customer->code;
                $nested_data['name'] = $customer->name;
                $nested_data['phone'] = $customer->phone;
                $nested_data['email'] = $customer->email;
                $nested_data['address'] = $customer->address;
                $nested_data['initial_due'] = $customer->initial_due;
                $nested_data['current_total_due'] = $customer->current_total_due;
                $nested_data['nid_front'] = $customer->nid_front;
                $nested_data['nid_back'] = $customer->nid_back;
                $nested_data['image'] = $customer->image;
                $nested_data['bank_detail_image'] = $customer->bank_detail_image;
                $nested_data['note'] = $customer->note;
                $nested_data['status'] = $customer->status;

                array_push($customer_arr, $nested_data);
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$customer_arr);
            return response()->json($response,200);

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function posCustomerCreate(Request $request){
        try {
            // required
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'phone' => 'required',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $get_customer_code = Customer::latest('id','desc')->pluck('code')->first();
            if(!empty($get_customer_code)){
                $get_customer_code_after_replace = str_replace("CC-","",$get_customer_code);
                $customer_code = $get_customer_code_after_replace+1;
            }else{
                $customer_code = 1;
            }
            $final_customer_code = 'CC-'.$customer_code;

            $customer = new Customer();
            $customer->customer_type = 'POS Sale';
            $customer->name = $request->name;
            $customer->shop_name = $request->shop_name;
            $customer->code = $final_customer_code;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = 0;
            $customer->current_total_due = 0;

            // nid_front
            $nid_front = $request->file('nid_front');
            if (isset($nid_front)) {
                $path = 'uploads/customers/';
                $field = $customer->nid_front;
                $customer->nid_front = ImageHelpers::imageUpload($nid_front,$path,$field);
            }else{
                $customer->nid_front = 'default.png';
            }

            // nid_back
            $nid_back = $request->file('nid_back');
            if (isset($nid_back)) {
                $path = 'uploads/customers/';
                $field = $customer->nid_back;
                $customer->nid_back = ImageHelpers::imageUpload($nid_back,$path,$field);
            }else{
                $customer->nid_back = 'default.png';
            }

            // image
            $image = $request->file('image');
            if (isset($image)) {
                $path = 'uploads/customers/';
                $field = $customer->image;
                $customer->image = ImageHelpers::imageUpload($image,$path,$field);
            }else{
                $customer->image = 'default.png';
            }

            // $bank_detail_image
            $bank_detail_image = $request->file('bank_detail_image');
            if (isset($bank_detail_image)) {
                $path = 'uploads/customers/';
                $field = $customer->bank_detail_image;
                $customer->bank_detail_image = ImageHelpers::imageUpload($bank_detail_image,$path,$field);
            }else{
                $customer->bank_detail_image = 'default.png';
            }
            $customer->save();
            $insert_id = $customer->id;

            if($insert_id){
                $account = DB::table('chart_of_accounts')
                    ->where('head_level',3)
                    ->where('head_code', 'like', '10203%')
                    ->Orderby('created_at', 'desc')
                    ->limit(1)
                    ->first();

                if(!empty($account)){
                    $head_code=$account->head_code+1;
                }else{
                    $head_code="1020300001";
                }
                $head_name = $request->name;

                $parent_head_name = 'Account Receivable';
                $head_level = 3;
                $head_type = 'A';

                $coa = new ChartOfAccount();
                $coa->head_debit_or_credit  = 'De';
                $coa->parent_head_name      = $parent_head_name;
                $coa->head_name             = $head_name;
                $coa->head_code             = $head_code;
                $coa->name_code             = $final_customer_code;
                $coa->head_type             = $head_type;
                $coa->head_level            = $head_level;
                $coa->is_active             = '1';
                $coa->is_transaction        = '1';
                $coa->is_general_ledger     = '1';
                $coa->ref_id                = $insert_id;
                $coa->user_bank_account_no  = NULL;
                $coa->created_by              = Auth::User()->id;
                $coa->updated_by              = Auth::User()->id;
                $coa->save();

                // customer initial due
                if($request->initial_due > 0) {
                    $get_voucher_name = 'Previous Balance';
                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',10)->latest()->pluck('voucher_no')->first();
                    if(!empty($get_voucher_no)){
                        $get_voucher_name_str = $get_voucher_name."-";
                        $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                        $voucher_no = $get_voucher+1;
                    }else{
                        $voucher_no = 8000;
                    }
                    $final_voucher_no = $get_voucher_name.'-'.$voucher_no;

                    $date = date('Y-m-d');
                    $year = date('Y');
                    $month = date('m');
                    $date_time = date('Y-m-d h:i:s');
                    $user_id = Auth::user()->id;

                    $cash_in_hand_account = ChartOfAccount::where('head_name','Cash In Hand')->first();
                    $description = 'Previous Balance of '.$coa->head_name;
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 8, $final_voucher_no, 'Previous Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $coa->id, $coa->head_code, $coa->head_name, $coa->parent_head_name, $coa->head_type, $request->initial_due, NULL, $description, 'Approved');
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 8, $final_voucher_no, 'Previous Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $cash_in_hand_account->id, $cash_in_hand_account->head_code, $cash_in_hand_account->head_name, $cash_in_hand_account->parent_head_name, $cash_in_hand_account->head_type, NULL, $request->initial_due, $description, 'Approved');
                }

                $response = APIHelpers::createAPIResponse(false,201,'Customer Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
                return response()->json($response,500);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function wholeCustomerCreate(Request $request){
        try {
            // required
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'phone' => 'required',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $get_customer_code = Customer::latest('id','desc')->pluck('code')->first();
            if(!empty($get_customer_code)){
                $get_customer_code_after_replace = str_replace("CC-","",$get_customer_code);
                $customer_code = $get_customer_code_after_replace+1;
            }else{
                $customer_code = 1;
            }
            $final_customer_code = 'CC-'.$customer_code;

            if(!empty($request->initial_due)){
                $initial_due = $request->initial_due;
            }else{
                $initial_due = 0;
            }

            $customer = new Customer();
            $customer->customer_type = 'Whole Sale';
            $customer->name = $request->name;
            $customer->shop_name = $request->shop_name;
            $customer->code = $final_customer_code;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = $initial_due;
            $customer->current_total_due = $initial_due;
            $customer->note = $request->note;

            // nid_front
            $nid_front = $request->file('nid_front');
            if (isset($nid_front)) {
                $path = 'uploads/customers/';
                $field = $customer->nid_front;
                $customer->nid_front = ImageHelpers::imageUpload($nid_front,$path,$field);
            }else{
                $customer->nid_front = 'default.png';
            }

            // nid_back
            $nid_back = $request->file('nid_back');
            if (isset($nid_back)) {
                $path = 'uploads/customers/';
                $field = $customer->nid_back;
                $customer->nid_back = ImageHelpers::imageUpload($nid_back,$path,$field);
            }else{
                $customer->nid_back = 'default.png';
            }

            // image
            $image = $request->file('image');
            if (isset($image)) {
                $path = 'uploads/customers/';
                $field = $customer->image;
                $customer->image = ImageHelpers::imageUpload($image,$path,$field);
            }else{
                $customer->image = 'default.png';
            }

            // $bank_detail_image
            $bank_detail_image = $request->file('bank_detail_image');
            if (isset($bank_detail_image)) {
                $path = 'uploads/customers/';
                $field = $customer->bank_detail_image;
                $customer->bank_detail_image = ImageHelpers::imageUpload($bank_detail_image,$path,$field);
            }else{
                $customer->bank_detail_image = 'default.png';
            }
            $customer->save();
            $insert_id = $customer->id;

            if($insert_id){
                $account = DB::table('chart_of_accounts')
                    ->where('head_level',3)
                    ->where('head_code', 'like', '10203%')
                    ->Orderby('created_at', 'desc')
                    ->limit(1)
                    ->first();

                if(!empty($account)){
                    $head_code=$account->head_code+1;
                }else{
                    $head_code="1020300001";
                }
                $head_name = $request->name.'-'.$final_customer_code;

                $parent_head_name = 'Account Receivable';
                $head_level = 3;
                $head_type = 'A';

                $coa = new ChartOfAccount();
                $coa->parent_head_name      = $parent_head_name;
                $coa->head_name             = $head_name;
                $coa->head_code             = $head_code;
                $coa->name_code             = $final_customer_code;
                $coa->head_type             = $head_type;
                $coa->head_level            = $head_level;
                $coa->is_active             = '1';
                $coa->is_transaction        = '1';
                $coa->is_general_ledger     = '1';
                $coa->ref_id                = $insert_id;
                $coa->user_bank_account_no  = NULL;
                $coa->created_by              = Auth::User()->id;
                $coa->updated_by              = Auth::User()->id;
                $coa->save();

                // customer initial due
                if($request->initial_due > 0) {
                    $get_voucher_name = 'Previous Balance';
                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',10)->latest()->pluck('voucher_no')->first();
                    if(!empty($get_voucher_no)){
                        $get_voucher_name_str = $get_voucher_name."-";
                        $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                        $voucher_no = $get_voucher+1;
                    }else{
                        $voucher_no = 8000;
                    }
                    $final_voucher_no = $get_voucher_name.'-'.$voucher_no;

                    $date = date('Y-m-d');
                    $year = date('Y');
                    $month = date('m');
                    $date_time = date('Y-m-d h:i:s');
                    $user_id = Auth::user()->id;

                    $cash_in_hand_account = ChartOfAccount::where('head_name','Cash In Hand')->first();
                    $description = 'Previous Balance of '.$coa->head_name;
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 8, $final_voucher_no, 'Previous Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $coa->id, $coa->head_code, $coa->head_name, $coa->parent_head_name, $coa->head_type, $request->initial_due, NULL, $description, 'Approved');
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 8, $final_voucher_no, 'Previous Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $cash_in_hand_account->id, $cash_in_hand_account->head_code, $cash_in_hand_account->head_name, $cash_in_hand_account->parent_head_name, $cash_in_hand_account->head_type, NULL, $request->initial_due, $description, 'Approved');
                }

                $customer_data = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'shop_name' => $customer->shop_name,
                    'phone' => $customer->phone
                ];

                $response = APIHelpers::createAPIResponse(false,201,'Customer Added Successfully.',$customer_data);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
                return response()->json($response,500);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function customerDetails(Request $request){
        try {
            $check_exists_customer = DB::table("customers")->where('id',$request->customer_id)->pluck('id')->first();
            if($check_exists_customer == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Customer Found.',null);
                return response()->json($response,404);
            }

            $customer = DB::table("customers")->where('id',$request->customer_id)->latest()->first();
            $response = APIHelpers::createAPIResponse(false,200,'',$customer);
            return response()->json($response,200);
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function customerUpdate(Request $request){
        try {
            $check_exists_customer = DB::table("customers")->where('id',$request->customer_id)->pluck('id')->first();
            if($check_exists_customer == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Customer Found.',null);
                return response()->json($response,404);
            }

            // required
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
                'name' => 'required',
                'phone' => 'required',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $customer = Customer::find($request->customer_id);
            $previous_initial_due = $customer->current_total_due;
            $previous_current_total_due = $customer->current_total_due;
            if($previous_initial_due > $request->initial_due){
                $increase_current_total_due = $previous_initial_due - $request->initial_due;
                $update_current_total_due = $previous_current_total_due + $increase_current_total_due;
            }else{
                $decrease_current_total_due = $request->initial_due - $previous_initial_due;
                $update_current_total_due = $previous_current_total_due + $decrease_current_total_due;
            }

            $customer->name = $request->name;
            $customer->shop_name = $request->shop_name;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = $request->initial_due;
            $customer->current_total_due = $update_current_total_due;
            $customer->note = $request->note;

            // nid_front
            $nid_front = $request->file('nid_front');
            if (isset($nid_front)) {
                $path = 'uploads/customers/';
                $field = $customer->nid_front;
                $customer->nid_front = ImageHelpers::imageUpload($nid_front,$path,$field);
            }else{
                $customer->nid_front = Customer::where('id',$customer->id)->pluck('nid_front')->first();
            }

            // nid_back
            $nid_back = $request->file('nid_back');
            if (isset($nid_back)) {
                $path = 'uploads/customers/';
                $field = $customer->nid_back;
                $customer->nid_back = ImageHelpers::imageUpload($nid_back,$path,$field);
            }else{
                $customer->nid_back = Customer::where('id',$customer->id)->pluck('nid_back')->first();
            }

            // image
            $image = $request->file('image');
            if (isset($image)) {
                $path = 'uploads/customers/';
                $field = $customer->image;
                $customer->image = ImageHelpers::imageUpload($image,$path,$field);
            }else{
                $customer->image = Customer::where('id',$customer->id)->pluck('image')->first();
            }

            // $bank_detail_image
            $bank_detail_image = $request->file('bank_detail_image');
            if (isset($bank_detail_image)) {
                $path = 'uploads/customers/';
                $field = $customer->bank_detail_image;
                $customer->bank_detail_image = ImageHelpers::imageUpload($bank_detail_image,$path,$field);
            }else{
                $customer->bank_detail_image = Customer::where('id',$customer->id)->pluck('bank_detail_image')->first();
            }

            $update_customer = $customer->save();

            if($update_customer){
                $coa = ChartOfAccount::where('name_code',$customer->code)->first();
                $coa->head_name=$request->name.'-'.$customer->code;
                $coa->save();

                // customer initial due
                if( ($previous_initial_due == 0) && ($request->initial_due > 0) ) {
                    $chart_of_account_transaction = ChartOfAccountTransaction::where('transaction_type','Previous Balance')
                    ->where('ref_id',$customer->id)
                    ->first();

                    if(!empty($chart_of_account_transaction)){
                        $chart_of_account_name = $customer->name . '-' . $customer->code;
                        $customer_previous_balance = ChartOfAccountTransactionDetail::where('payment_type_id', 8)
                            ->where('chart_of_account_name', $chart_of_account_name)
                            ->first();
                        if (!empty($customer_previous_balance)) {
                            $customer_previous_balance->debit = NULL;
                            $customer_previous_balance->credit = $request->initial_due;
                            $customer_previous_balance->save();
                        }

                        // Cash In Hand account
                        $cash_in_hand_previous_balance = ChartOfAccountTransactionDetail::where('payment_type_id', 8)
                            ->where('chart_of_account_name', 'Cash In Hand')
                            ->first();
                        if (!empty($cash_in_hand_previous_balance)) {
                            $cash_in_hand_previous_balance->debit = $request->initial_due;
                            $cash_in_hand_previous_balance->credit = NULL;
                            $cash_in_hand_previous_balance->save();
                        }
                    }else{
                        $get_voucher_name = 'Previous Balance';
                        $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',10)->latest()->pluck('voucher_no')->first();
                        if(!empty($get_voucher_no)){
                            $get_voucher_name_str = $get_voucher_name."-";
                            $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                            $voucher_no = $get_voucher+1;
                        }else{
                            $voucher_no = 8000;
                        }
                        $final_voucher_no = $get_voucher_name.'-'.$voucher_no;

                        $date = date('Y-m-d');
                        $year = date('Y');
                        $month = date('m');
                        $date_time = date('Y-m-d h:i:s');
                        $user_id = Auth::user()->id;

                        $cash_in_hand_account = ChartOfAccount::where('head_name','Cash In Hand')->first();
                        $description = 'Previous Balance of '.$coa->head_name;
                        chartOfAccountTransactionDetails($request->customer_id, NULL, $user_id, 8, $final_voucher_no, 'Previous Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $coa->id, $coa->head_code, $coa->head_name, $coa->parent_head_name, $coa->head_type, $request->initial_due, NULL, $description, 'Approved');
                        chartOfAccountTransactionDetails($request->customer_id, NULL, $user_id, 8, $final_voucher_no, 'Previous Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $cash_in_hand_account->id, $cash_in_hand_account->head_code, $cash_in_hand_account->head_name, $cash_in_hand_account->parent_head_name, $cash_in_hand_account->head_type, NULL, $request->initial_due, $description, 'Approved');
                    }
                }elseif( $request->initial_due !== $previous_initial_due ){

                    $chart_of_account_name = $customer->name.'-'.$customer->code;
                    $customer_previous_balance = ChartOfAccountTransactionDetail::where('payment_type_id',8)
                        ->where('chart_of_account_name',$chart_of_account_name)
                        ->first();
                    if(!empty($customer_previous_balance)){
                        $customer_previous_balance->debit = NULL;
                        $customer_previous_balance->credit = $request->initial_due;
                        $customer_previous_balance->save();
                    }

                    // Cash In Hand account
                    $cash_in_hand_previous_balance = ChartOfAccountTransactionDetail::where('payment_type_id',8)
                        ->where('chart_of_account_name','Cash In Hand')
                        ->first();
                    if(!empty($cash_in_hand_previous_balance)){
                        $cash_in_hand_previous_balance->debit = $request->initial_due;
                        $cash_in_hand_previous_balance->credit = NULL;
                        $cash_in_hand_previous_balance->save();
                    }
                }


                $response = APIHelpers::createAPIResponse(false,200,'Customer Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Customer Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function customerDelete(Request $request){
        try {
            $check_exists_customer = DB::table("customers")->where('id',$request->customer_id)->pluck('id')->first();
            if($check_exists_customer == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Customer Found.',null);
                return response()->json($response,404);
            }

            $soft_delete_customer = Customer::find($request->customer_id);
            $soft_delete_customer->status=0;
            $affected_row = $soft_delete_customer->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Customer Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Customer Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function posSaleCustomerListPaginationWithSearch(Request $request){
        if($request->search){
            $search = $request->search;
            $customers = Customer::where('customer_type','POS Sale')
                ->where(function ($query) use ($search) {
                    $query->where('name','like','%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                })
                ->latest()->paginate(12);
            return new CustomerCollection($customers);
        }else{
            return new CustomerCollection(Customer::where('customer_type','POS Sale')->latest()->paginate(12));
        }
    }

    public function wholeSaleCustomerListPaginationWithSearch(Request $request){
        if($request->search){
            $search = $request->search;
            $customers = Customer::where('customer_type','Whole Sale')
                ->where(function ($query) use ($search) {
                    $query->where('name','like','%'.$search.'%')
                        ->orWhere('shop_name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%');
                })
                ->latest()->paginate(12);
            return new CustomerCollection($customers);
        }else{
            return new CustomerCollection(Customer::where('customer_type','Whole Sale')->latest()->paginate(12));
        }
    }

    public function customerCurrentTotalDueByCustomerId(Request $request){
        try {
            $current_total_due = customerCurrentTotalDueByCustomerId($request->customer_id);

            if($current_total_due !== 0){
                return response()->json(['success'=>true,'code' => 200,'data' => $current_total_due], 200);
            }else{
                return response()->json(['success'=>false,'code' => 400, 'message' => 'No Customer Found.'], 400);
            }

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function customerDuePaid(Request $request){
        try {
        $customer_id = $request->customer_id;
        $payment_type_id = $request->payment_type_id;
        $paid_amount = $request->paid_amount;
        $description = $request->description;

        $check_exists_customer = DB::table("customers")->where('id',$customer_id)->pluck('id')->first();
        if($check_exists_customer == null){
            $response = APIHelpers::createAPIResponse(true,404,'No Customer Found.',null);
            return response()->json($response,404);
        }

        // required
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'paid_amount' => 'required',
            'due_amount' => 'required',
            'payment_type_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }

        $customer = Customer::find($customer_id);
        $previous_current_total_due = $customer->current_total_due;
        $update_current_total_due = $previous_current_total_due - $paid_amount;

        $customer->current_total_due = $update_current_total_due;
        $affected_row = $customer->save();

        if($affected_row){
            // posting
            $date = date('Y-m-d');
            $user_id = Auth::user()->id;
            $warehouse_id = NULL;
            $store_id = NULL;
            $month = date('m');
            $year = date('Y');
            $transaction_date_time = date('Y-m-d H:i:s');

            // customer head
            $code = Customer::where('id',$customer_id)->pluck('code')->first();
            $customer_chart_of_account_info = ChartOfAccount::where('name_code',$code)->first();

            // Cash In Hand For Paid Amount
            $get_voucher_name = VoucherType::where('id',2)->pluck('name')->first();
            $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',2)->latest()->pluck('voucher_no')->first();
            if(!empty($get_voucher_no)){
                $get_voucher_name_str = $get_voucher_name."-";
                $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                $voucher_no = $get_voucher+1;
            }else{
                $voucher_no = 2000;
            }
            $final_voucher_no = $get_voucher_name.'-'.$voucher_no;

            // Cash In Hand Account Info
            $cash_chart_of_account_info = ChartOfAccount::where('head_name','Cash In Hand')->first();

            // Cheque Account Info
            $cheque_chart_of_account_info = ChartOfAccount::where('head_name', 'Cheque')->first();

            // Account Receivable Account Info
            $account_receivable_info = ChartOfAccount::where('head_name','Account Receivable')->first();

            if($payment_type_id == '1') {
                // Cash In Hand debit
                $description = $description ? $description : $cash_chart_of_account_info->head_name . ' Debited For Due Amount Paid';
                chartOfAccountTransactionDetails(NULL, NULL, $user_id, 2, $final_voucher_no, 'Due Paid', $date, $transaction_date_time, $year, $month, NULL, NULL, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
            }

            if($payment_type_id == '2') {
                // Cheque debit
                $description = $description ? $description : $cheque_chart_of_account_info->head_name . ' Debited For Due Amount Paid';
                chartOfAccountTransactionDetails(NULL, NULL, $user_id, 2, $final_voucher_no, 'Due Paid', $date, $transaction_date_time, $year, $month, NULL, NULL, $payment_type_id, NULL, NULL, NULL, $cheque_chart_of_account_info->id, $cheque_chart_of_account_info->head_code, $cheque_chart_of_account_info->head_name, $cheque_chart_of_account_info->parent_head_name, $cheque_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
            }

            // Account Receivable credit
            //$description = $description ? $description : $account_receivable_info->head_name . ' Credited For Due Amount Paid';
            //chartOfAccountTransactionDetails(NULL, NULL, $user_id, 2, $final_voucher_no, 'Due Paid', $date, $transaction_date_time, $year, $month, NULL, NULL, $payment_type_id, NULL, NULL, NULL, $account_receivable_info->id, $account_receivable_info->head_code, $account_receivable_info->head_name, $account_receivable_info->parent_head_name, $account_receivable_info->head_type, NULL, $paid_amount, $description, 'Approved');

            // Customer Credit
            $description = $customer_chart_of_account_info->head_name.' Customer Debited For Paid Amount Sales Due';
            chartOfAccountTransactionDetails(NULL, NULL, $user_id, 2, $final_voucher_no, 'Due Paid', $date, $transaction_date_time, $year, $month, NULL, NULL, $payment_type_id, NULL, NULL, NULL, $customer_chart_of_account_info->id, $customer_chart_of_account_info->head_code, $customer_chart_of_account_info->head_name, $customer_chart_of_account_info->parent_head_name, $customer_chart_of_account_info->head_type, NULL, $paid_amount, $description, 'Approved');

            $response = APIHelpers::createAPIResponse(false,200,'Supplier Updated Successfully.',null);
            return response()->json($response,200);
        }else{
            $response = APIHelpers::createAPIResponse(true,400,'Supplier Updated Failed.',null);
            return response()->json($response,400);
        }
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
