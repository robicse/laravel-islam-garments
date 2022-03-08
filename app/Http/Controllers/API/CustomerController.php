<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Customer;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerCollection;
use App\VoucherType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

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
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    // posCustomerActiveList
    public function wholeCustomerActiveList(){
        try {
            $customers = DB::table('customers')
                ->select('id','name','status')
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
            //return $e->getMessage();
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
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function posCustomerCreate(Request $request){

//        try {
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

            $image = $request->file('nid_front');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->nid_front))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->nid_front);

                }

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->nid_front = $imagename;

            }else{
                $customer->nid_front = 'default.png';
            }

            $image = $request->file('nid_back');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->nid_back))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->nid_back);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->nid_back = $imagename;

            }else{
                $customer->nid_back = 'default.png';
            }

            $image = $request->file('image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->image))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->image = $imagename;

            }else{
                $customer->image = 'default.png';
            }

            $image = $request->file('bank_detail_image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->bank_detail_image))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->bank_detail_image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->bank_detail_image = $imagename;

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
                    $get_voucher_name = 'Opening Balance';
                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',8)->latest()->pluck('voucher_no')->first();
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

                    $chart_of_account_transactions = new ChartOfAccountTransaction();
                    $chart_of_account_transactions->ref_id = $customer->id;
                    $chart_of_account_transactions->transaction_type = 'Opening Balance of '.$coa->head_name;
                    $chart_of_account_transactions->user_id = $user_id;
                    $chart_of_account_transactions->warehouse_id = NULL;
                    $chart_of_account_transactions->store_id = NULL;
                    $chart_of_account_transactions->payment_type_id = 1;
                    $chart_of_account_transactions->voucher_type_id = 8;
                    $chart_of_account_transactions->voucher_no = $final_voucher_no;
                    $chart_of_account_transactions->is_approved = 'approved';
                    $chart_of_account_transactions->transaction_date = $date;
                    $chart_of_account_transactions->transaction_date_time = $date_time;
                    $chart_of_account_transactions->save();
                    $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;

                    if($chart_of_account_transactions_insert_id) {
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = NULL;
                        $chart_of_account_transactions->payment_type_id = 1;
                        $chart_of_account_transaction_details->payment_type_id = 8;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $coa->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $coa->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $coa->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $coa->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $coa->head_type;
                        $chart_of_account_transaction_details->debit = $request->initial_due;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = 'Opening Balance of '.$coa->head_name;
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
                        $chart_of_account_transaction_details->save();

                        // Cash In Hand account
                        $cash_in_hand_account = ChartOfAccount::where('head_name','Cash In Hand')->first();
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = NULL;
                        $chart_of_account_transaction_details->payment_type_id = 8;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $cash_in_hand_account->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $cash_in_hand_account->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $cash_in_hand_account->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_in_hand_account->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $cash_in_hand_account->head_type;
                        $chart_of_account_transaction_details->debit = NULL;
                        $chart_of_account_transaction_details->credit = $request->initial_due;
                        $chart_of_account_transaction_details->description = 'Opening Balance of '.$coa->head_name;
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
                        $chart_of_account_transaction_details->save();
                    }
                }

                $response = APIHelpers::createAPIResponse(false,201,'Customer Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
                return response()->json($response,500);
            }
//        } catch (\Exception $e) {
//            //return $e->getMessage();
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }

    public function wholeCustomerCreate(Request $request){

//        try {
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
            $customer->customer_type = 'Whole Sale';
            $customer->name = $request->name;
            $customer->shop_name = $request->shop_name;
            $customer->code = $final_customer_code;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = $request->initial_due;
            $customer->current_total_due = $request->initial_due;
            $customer->note = $request->note;
            $image = $request->file('nid_front');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->nid_front))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->nid_front);

                }

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->nid_front = $imagename;

            }else{
                $customer->nid_front = 'default.png';
            }

            $image = $request->file('nid_back');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->nid_back))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->nid_back);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->nid_back = $imagename;

            }else{
                $customer->nid_back = 'default.png';
            }

            $image = $request->file('image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->image))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->image = $imagename;

            }else{
                $customer->image = 'default.png';
            }

            $image = $request->file('bank_detail_image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->bank_detail_image))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->bank_detail_image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->bank_detail_image = $imagename;

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
                    $get_voucher_name = 'Opening Balance';
                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',8)->latest()->pluck('voucher_no')->first();
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

                    $chart_of_account_transactions = new ChartOfAccountTransaction();
                    $chart_of_account_transactions->ref_id = $customer->id;
                    $chart_of_account_transactions->transaction_type = 'Opening Balance';
                    $chart_of_account_transactions->user_id = $user_id;
                    $chart_of_account_transactions->warehouse_id = NULL;
                    $chart_of_account_transactions->store_id = NULL;
                    $chart_of_account_transactions->payment_type_id = 1;
                    $chart_of_account_transactions->voucher_type_id = 8;
                    $chart_of_account_transactions->voucher_no = $final_voucher_no;
                    $chart_of_account_transactions->is_approved = 'approved';
                    $chart_of_account_transactions->transaction_date = $date;
                    $chart_of_account_transactions->transaction_date_time = $date_time;
                    $chart_of_account_transactions->save();
                    $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;

                    if($chart_of_account_transactions_insert_id) {

                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = NULL;
                        $chart_of_account_transactions->payment_type_id = 1;
                        $chart_of_account_transaction_details->payment_type_id = 8;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $coa->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $coa->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $coa->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $coa->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $coa->head_type;
                        $chart_of_account_transaction_details->debit = $request->initial_due;
                        $chart_of_account_transaction_details->credit = NULL;
                        $chart_of_account_transaction_details->description = 'Opening Balance';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
                        $chart_of_account_transaction_details->save();

                        // Cash In Hand account
                        $supplier_account = ChartOfAccount::where('head_name','Cash In Hand')->first();
                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                        $chart_of_account_transaction_details->warehouse_id = NULL;
                        $chart_of_account_transaction_details->store_id = NULL;
                        $chart_of_account_transaction_details->payment_type_id = 8;
                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                        $chart_of_account_transaction_details->chart_of_account_id = $supplier_account->id;
                        $chart_of_account_transaction_details->chart_of_account_number = $supplier_account->head_code;
                        $chart_of_account_transaction_details->chart_of_account_name = $supplier_account->head_name;
                        $chart_of_account_transaction_details->chart_of_account_parent_name = $supplier_account->parent_head_name;
                        $chart_of_account_transaction_details->chart_of_account_type = $supplier_account->head_type;
                        $chart_of_account_transaction_details->debit = NULL;
                        $chart_of_account_transaction_details->credit = $request->initial_due;
                        $chart_of_account_transaction_details->description = 'Opening Balance';
                        $chart_of_account_transaction_details->year = $year;
                        $chart_of_account_transaction_details->month = $month;
                        $chart_of_account_transaction_details->transaction_date = $date;
                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
                        $chart_of_account_transaction_details->save();
                    }
                }

                $response = APIHelpers::createAPIResponse(false,201,'Customer Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
                return response()->json($response,500);
            }
//        } catch (\Exception $e) {
//            //return $e->getMessage();
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
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
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function customerUpdate(Request $request){
//        try {
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


            //$customer->customer_type = $request->customer_type;
            $customer->name = $request->name;
            $customer->shop_name = $request->shop_name;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = $request->initial_due;
            $customer->current_total_due = $update_current_total_due;
            $customer->note = $request->note;
            $image = $request->file('nid_front');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->nid_front))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->nid_front);

                }

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->nid_front = $imagename;

            }else{
                $customer->nid_front = Customer::where('id',$customer->id)->pluck('nid_front')->first();
            }

            $image = $request->file('nid_back');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->nid_back))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->nid_back);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->nid_back = $imagename;

            }else{
                $customer->nid_back = Customer::where('id',$customer->id)->pluck('nid_back')->first();
            }

            $image = $request->file('image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->image))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->image = $imagename;

            }else{
                $customer->image = Customer::where('id',$customer->id)->pluck('image')->first();
            }

            $image = $request->file('bank_detail_image');
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/customers/'.$customer->bank_detail_image))
                {
                    Storage::disk('public')->delete('uploads/customers/'.$customer->bank_detail_image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/customers/'. $imagename, $proImage);

                // update image db
                $customer->bank_detail_image = $imagename;

            }else{
                $customer->bank_detail_image = Customer::where('id',$customer->id)->pluck('bank_detail_image')->first();
            }
            $update_customer = $customer->save();

            if($update_customer){
                // customer initial due
                if( ($previous_initial_due == 0) && ($request->initial_due > 0) ) {
                    $chart_of_account_transaction = ChartOfAccountTransaction::where('transaction_type','Opening Balance')
                    ->where('ref_id',$customer->id)
                    ->first();

                    if(!empty($chart_of_account_transaction)){
                        $chart_of_account_name = $customer->name . '-' . $customer->code;
                        $customer_opening_balance = ChartOfAccountTransactionDetail::where('payment_type_id', 8)
                            ->where('chart_of_account_name', $chart_of_account_name)
                            ->first();
                        if (!empty($customer_opening_balance)) {
                            $customer_opening_balance->debit = NULL;
                            $customer_opening_balance->credit = $request->initial_due;
                            $customer_opening_balance->save();
                        }

                        // Cash In Hand account
                        $cash_in_hand_opening_balance = ChartOfAccountTransactionDetail::where('payment_type_id', 8)
                            ->where('chart_of_account_name', 'Cash In Hand')
                            ->first();
                        if (!empty($cash_in_hand_opening_balance)) {
                            $cash_in_hand_opening_balance->debit = $request->initial_due;
                            $cash_in_hand_opening_balance->credit = NULL;
                            $cash_in_hand_opening_balance->save();
                        }
                    }else{
                        $get_voucher_name = 'Opening Balance';
                        $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',8)->latest()->pluck('voucher_no')->first();
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

                        $chart_of_account_transactions = new ChartOfAccountTransaction();
                        $chart_of_account_transactions->ref_id = $customer->id;
                        $chart_of_account_transactions->transaction_type = 'Opening Balance';
                        $chart_of_account_transactions->user_id = $user_id;
                        $chart_of_account_transactions->warehouse_id = NULL;
                        $chart_of_account_transactions->store_id = NULL;
                        $chart_of_account_transactions->payment_type_id = 1;
                        $chart_of_account_transactions->voucher_type_id = 8;
                        $chart_of_account_transactions->voucher_no = $final_voucher_no;
                        $chart_of_account_transactions->is_approved = 'approved';
                        $chart_of_account_transactions->transaction_date = $date;
                        $chart_of_account_transactions->transaction_date_time = $date_time;
                        $chart_of_account_transactions->save();
                        $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;

                        if($chart_of_account_transactions_insert_id) {
                            $chart_of_account_name = $customer->name.'-'.$customer->code;
                            $coa = ChartOfAccount::where('head_name',$chart_of_account_name)->first();

                            $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                            $chart_of_account_transaction_details->warehouse_id = NULL;
                            $chart_of_account_transaction_details->store_id = NULL;
                            $chart_of_account_transactions->payment_type_id = 1;
                            $chart_of_account_transaction_details->payment_type_id = 8;
                            $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                            $chart_of_account_transaction_details->chart_of_account_id = $coa->id;
                            $chart_of_account_transaction_details->chart_of_account_number = $coa->head_code;
                            $chart_of_account_transaction_details->chart_of_account_name = $coa->head_name;
                            $chart_of_account_transaction_details->chart_of_account_parent_name = $coa->parent_head_name;
                            $chart_of_account_transaction_details->chart_of_account_type = $coa->head_type;
                            $chart_of_account_transaction_details->debit = $request->initial_due;
                            $chart_of_account_transaction_details->credit = NULL;
                            $chart_of_account_transaction_details->description = 'Opening Balance';
                            $chart_of_account_transaction_details->year = $year;
                            $chart_of_account_transaction_details->month = $month;
                            $chart_of_account_transaction_details->transaction_date = $date;
                            $chart_of_account_transaction_details->transaction_date_time = $date_time;
                            $chart_of_account_transaction_details->save();

                            // Cash In Hand account
                            $supplier_account = ChartOfAccount::where('head_name','Cash In Hand')->first();
                            $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                            $chart_of_account_transaction_details->warehouse_id = NULL;
                            $chart_of_account_transaction_details->store_id = NULL;
                            $chart_of_account_transaction_details->payment_type_id = 8;
                            $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                            $chart_of_account_transaction_details->chart_of_account_id = $supplier_account->id;
                            $chart_of_account_transaction_details->chart_of_account_number = $supplier_account->head_code;
                            $chart_of_account_transaction_details->chart_of_account_name = $supplier_account->head_name;
                            $chart_of_account_transaction_details->chart_of_account_parent_name = $supplier_account->parent_head_name;
                            $chart_of_account_transaction_details->chart_of_account_type = $supplier_account->head_type;
                            $chart_of_account_transaction_details->debit = NULL;
                            $chart_of_account_transaction_details->credit = $request->initial_due;
                            $chart_of_account_transaction_details->description = 'Opening Balance';
                            $chart_of_account_transaction_details->year = $year;
                            $chart_of_account_transaction_details->month = $month;
                            $chart_of_account_transaction_details->transaction_date = $date;
                            $chart_of_account_transaction_details->transaction_date_time = $date_time;
                            $chart_of_account_transaction_details->save();
                        }
                    }
                }elseif( $request->initial_due !== $previous_initial_due ){
                    $chart_of_account = ChartOfAccount::where('name_code',$customer->code)->first();
                    $chart_of_account->head_name=$request->name.'-'.$customer->code;
                    $chart_of_account->save();

                    $chart_of_account_name = $customer->name.'-'.$customer->code;
                    $customer_opening_balance = ChartOfAccountTransactionDetail::where('payment_type_id',8)
                        ->where('chart_of_account_name',$chart_of_account_name)
                        ->first();
                    if(!empty($customer_opening_balance)){
                        $customer_opening_balance->debit = NULL;
                        $customer_opening_balance->credit = $request->initial_due;
                        $customer_opening_balance->save();
                    }

                    // Cash In Hand account
                    $cash_in_hand_opening_balance = ChartOfAccountTransactionDetail::where('payment_type_id',8)
                        ->where('chart_of_account_name','Cash In Hand')
                        ->first();
                    if(!empty($cash_in_hand_opening_balance)){
                        $cash_in_hand_opening_balance->debit = $request->initial_due;
                        $cash_in_hand_opening_balance->credit = NULL;
                        $cash_in_hand_opening_balance->save();
                    }
                }


                $response = APIHelpers::createAPIResponse(false,200,'Customer Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Customer Updated Failed.',null);
                return response()->json($response,400);
            }
//        } catch (\Exception $e) {
//            //return $e->getMessage();
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
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
            //return $e->getMessage();
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
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
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
//        try {
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

            // supplier head
            $code = Customer::where('id',$customer_id)->pluck('code')->first();
            //$customer_chart_of_account_info = ChartOfAccount::where('name_code',$code)->first();

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

            $chart_of_account_transactions = new ChartOfAccountTransaction();
            $chart_of_account_transactions->ref_id = NULL;
            $chart_of_account_transactions->user_id = $user_id;
            $chart_of_account_transactions->warehouse_id = $warehouse_id;
            $chart_of_account_transactions->store_id = $store_id;
            $chart_of_account_transactions->payment_type_id = $payment_type_id;
            $chart_of_account_transactions->transaction_type = 'Due Paid';
            $chart_of_account_transactions->voucher_type_id = 2;
            $chart_of_account_transactions->voucher_no = $final_voucher_no;
            $chart_of_account_transactions->is_approved = 'approved';
            $chart_of_account_transactions->transaction_date = $date;
            $chart_of_account_transactions->transaction_date_time = $transaction_date_time;
            $chart_of_account_transactions->save();
            $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;

            if($chart_of_account_transactions_insert_id){
                if($payment_type_id == '1') {
                    // Cash In Hand debit
                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                    $chart_of_account_transaction_details->store_id = $store_id;
                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                    $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
                    $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
                    $chart_of_account_transaction_details->chart_of_account_name = $cash_chart_of_account_info->head_name;
                    $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
                    $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
                    $chart_of_account_transaction_details->debit = $paid_amount;
                    $chart_of_account_transaction_details->credit = NULL;
                    $chart_of_account_transaction_details->description = $description ? $description : $cash_chart_of_account_info->head_name . ' Debited For Due Amount Paid';
                    $chart_of_account_transaction_details->year = $year;
                    $chart_of_account_transaction_details->month = $month;
                    $chart_of_account_transaction_details->transaction_date = $date;
                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transaction_details->save();

                    // Account Receivable credit
                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                    $chart_of_account_transaction_details->store_id = $store_id;
                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                    $chart_of_account_transaction_details->chart_of_account_id = $account_receivable_info->id;
                    $chart_of_account_transaction_details->chart_of_account_number = $account_receivable_info->head_code;
                    $chart_of_account_transaction_details->chart_of_account_name = $account_receivable_info->head_name;
                    $chart_of_account_transaction_details->chart_of_account_parent_name = $account_receivable_info->parent_head_name;
                    $chart_of_account_transaction_details->chart_of_account_type = $account_receivable_info->head_type;
                    $chart_of_account_transaction_details->debit = NULL;
                    $chart_of_account_transaction_details->credit = $paid_amount;
                    $chart_of_account_transaction_details->description = $description ? $description : $account_receivable_info->head_name . ' Credited For Due Amount Paid';
                    $chart_of_account_transaction_details->year = $year;
                    $chart_of_account_transaction_details->month = $month;
                    $chart_of_account_transaction_details->transaction_date = $date;
                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transaction_details->save();
                }

                if($payment_type_id == '2') {
                    // Cheque debit
                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                    $chart_of_account_transaction_details->store_id = $store_id;
                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                    $chart_of_account_transaction_details->chart_of_account_id = $cheque_chart_of_account_info->id;
                    $chart_of_account_transaction_details->chart_of_account_number = $cheque_chart_of_account_info->head_code;
                    $chart_of_account_transaction_details->chart_of_account_name = $cheque_chart_of_account_info->head_name;
                    $chart_of_account_transaction_details->chart_of_account_parent_name = $cheque_chart_of_account_info->parent_head_name;
                    $chart_of_account_transaction_details->chart_of_account_type = $cheque_chart_of_account_info->head_type;
                    $chart_of_account_transaction_details->debit = $paid_amount;
                    $chart_of_account_transaction_details->credit = NULL;
                    $chart_of_account_transaction_details->description = $description ? $description : $cheque_chart_of_account_info->head_name . ' Debited For Due Amount Paid';
                    $chart_of_account_transaction_details->year = $year;
                    $chart_of_account_transaction_details->month = $month;
                    $chart_of_account_transaction_details->transaction_date = $date;
                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transaction_details->save();

                    // Account Receivable credit
                    $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
                    $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
                    $chart_of_account_transaction_details->store_id = $store_id;
                    $chart_of_account_transaction_details->payment_type_id = $payment_type_id;
                    $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
                    $chart_of_account_transaction_details->chart_of_account_id = $account_receivable_info->id;
                    $chart_of_account_transaction_details->chart_of_account_number = $account_receivable_info->head_code;
                    $chart_of_account_transaction_details->chart_of_account_name = $account_receivable_info->head_name;
                    $chart_of_account_transaction_details->chart_of_account_parent_name = $account_receivable_info->parent_head_name;
                    $chart_of_account_transaction_details->chart_of_account_type = $account_receivable_info->head_type;
                    $chart_of_account_transaction_details->debit = NULL;
                    $chart_of_account_transaction_details->credit = $paid_amount;
                    $chart_of_account_transaction_details->description = $description ? $description : $account_receivable_info->head_name . ' Credited For Due Amount Paid';
                    $chart_of_account_transaction_details->year = $year;
                    $chart_of_account_transaction_details->month = $month;
                    $chart_of_account_transaction_details->transaction_date = $date;
                    $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                    $chart_of_account_transaction_details->save();
                }
            }

            $response = APIHelpers::createAPIResponse(false,200,'Supplier Updated Successfully.',null);
            return response()->json($response,200);
        }else{
            $response = APIHelpers::createAPIResponse(true,400,'Supplier Updated Failed.',null);
            return response()->json($response,400);
        }
//        } catch (\Exception $e) {
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }
}
