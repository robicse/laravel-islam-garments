<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Customer;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerCollection;
use App\Party;
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
    // customer
    public function customerList(){
        try {
            $customers = DB::table('customers')
                ->select('id','customer_type','code','name','phone','email','address','status')
                ->orderBy('id','desc')
                ->get();

            if($customers === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Customers Found.',null);
                return response()->json($response,404);
            }

            $customer_arr = [];
            foreach ($customers as $customer) {
                $nested_data['id'] = $customer->id;
                $nested_data['customer_type'] = $customer->customer_type;
                $nested_data['code'] = $customer->code;
                $nested_data['name'] = $customer->name;
                $nested_data['phone'] = $customer->phone;
                $nested_data['email'] = $customer->email;
                $nested_data['address'] = $customer->address;
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
            $customer->code = $final_customer_code;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = 0;

            $image = $request->file('nid_front');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->nid_front))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->nid_front);

                }

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->nid_back))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->nid_back);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->image))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->bank_detail_image))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->bank_detail_image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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

                // whole customer initial due
//                if($request->initial_due > 0){
//                    $get_voucher_name = VoucherType::where('id',2)->pluck('name')->first();
//                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',2)->latest()->pluck('voucher_no')->first();
//                    if(!empty($get_voucher_no)){
//                        $get_voucher_name_str = $get_voucher_name."-";
//                        $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
//                        $voucher_no = $get_voucher+1;
//                    }else{
//                        $voucher_no = 8000;
//                    }
//                    $final_voucher_no = $get_voucher_name.'-'.$voucher_no;
//
//                    $date = date('Y-m-d');
//                    $year = date('Y');
//                    $month = date('m');
//                    $date_time = date('Y-m-d h:i:s');
//                    $user_id = Auth::user()->id;
//                    //$warehouse_id = 1;
//
//                    $chart_of_account_transactions = new ChartOfAccountTransaction();
//                    $chart_of_account_transactions->ref_id = $insert_id;
//                    $chart_of_account_transactions->transaction_type = 'Initial Due';
//                    $chart_of_account_transactions->user_id = $user_id;
//                    $chart_of_account_transactions->warehouse_id = $warehouse_id;
//                    $chart_of_account_transactions->store_id = NULL;
//                    $chart_of_account_transactions->voucher_type_id = 2;
//                    $chart_of_account_transactions->voucher_no = $final_voucher_no;
//                    $chart_of_account_transactions->is_approved = 'approved';
//                    $chart_of_account_transactions->transaction_date = $date;
//                    $chart_of_account_transactions->transaction_date_time = $date_time;
//                    $chart_of_account_transactions->save();
//                    $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;
//
//                    if($chart_of_account_transactions_insert_id) {
//
//                        // customer account
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                        $chart_of_account_transaction_details->store_id = NULL;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $coa->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $coa->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $coa->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $coa->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $coa->head_type;
//                        $chart_of_account_transaction_details->debit = $request->initial_due;
//                        $chart_of_account_transaction_details->credit = NULL;
//                        $chart_of_account_transaction_details->description = 'Initial Due';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
//                        $chart_of_account_transaction_details->save();
//
//                        // Account Receivable
//                        $cash_chart_of_account_info = ChartOfAccount::where('head_name', 'Account Receivable')->first();
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = $warehouse_id;
//                        $chart_of_account_transaction_details->store_id = NULL;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = 'Account Receivable';
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = NULL;
//                        $chart_of_account_transaction_details->credit = $request->initial_due;
//                        $chart_of_account_transaction_details->description = 'Initial Due';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
//                        $chart_of_account_transaction_details->save();
//
//                    }
//                }

                $response = APIHelpers::createAPIResponse(false,201,'Customer Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
                return response()->json($response,500);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
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

            $customer = new Customer();
            $customer->customer_type = 'Whole Sale';
            $customer->name = $request->name;
            $customer->code = $final_customer_code;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = $request->initial_due;
            $image = $request->file('nid_front');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/suppliers/'.$customer->nid_front))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$customer->nid_front);

                }

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$customer->nid_back))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$customer->nid_back);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$customer->image))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$customer->image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$customer->bank_detail_image))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$customer->bank_detail_image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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

                // whole customer initial due
//                if($request->initial_due > 0){
//                    $get_voucher_name = VoucherType::where('id',2)->pluck('name')->first();
//                    $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',2)->latest()->pluck('voucher_no')->first();
//                    if(!empty($get_voucher_no)){
//                        $get_voucher_name_str = $get_voucher_name."-";
//                        $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
//                        $voucher_no = $get_voucher+1;
//                    }else{
//                        $voucher_no = 8000;
//                    }
//                    $final_voucher_no = $get_voucher_name.'-'.$voucher_no;
//
//                    $date = date('Y-m-d');
//                    $year = date('Y');
//                    $month = date('m');
//                    $date_time = date('Y-m-d h:i:s');
//                    $user_id = Auth::user()->id;
//                    //$warehouse_id = 1;
//
//                    $chart_of_account_transactions = new ChartOfAccountTransaction();
//                    $chart_of_account_transactions->ref_id = $insert_id;
//                    $chart_of_account_transactions->transaction_type = 'Initial Due';
//                    $chart_of_account_transactions->user_id = $user_id;
//                    $chart_of_account_transactions->warehouse_id = NULL;
//                    $chart_of_account_transactions->store_id = NULL;
//                    $chart_of_account_transactions->voucher_type_id = 2;
//                    $chart_of_account_transactions->voucher_no = $final_voucher_no;
//                    $chart_of_account_transactions->is_approved = 'approved';
//                    $chart_of_account_transactions->transaction_date = $date;
//                    $chart_of_account_transactions->transaction_date_time = $date_time;
//                    $chart_of_account_transactions->save();
//                    $chart_of_account_transactions_insert_id = $chart_of_account_transactions->id;
//
//                    if($chart_of_account_transactions_insert_id) {

//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = NULL;
//                        $chart_of_account_transaction_details->store_id = NULL;
//                        $chart_of_account_transaction_details->payment_type_id = 2;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $coa->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $coa->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $coa->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $coa->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $coa->head_type;
//                        $chart_of_account_transaction_details->debit = $request->initial_due;
//                        $chart_of_account_transaction_details->credit = NULL;
//                        $chart_of_account_transaction_details->description = 'Initial Due';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
//                        $chart_of_account_transaction_details->save();

                        // customer account
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = NULL;
//                        $chart_of_account_transaction_details->store_id = NULL;
//                        $chart_of_account_transaction_details->payment_type_id = 2;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $coa->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $coa->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = $coa->head_name;
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $coa->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $coa->head_type;
//                        $chart_of_account_transaction_details->debit = NULL;
//                        $chart_of_account_transaction_details->credit = $request->initial_due;
//                        $chart_of_account_transaction_details->description = 'Initial Due';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
//                        $chart_of_account_transaction_details->save();

                        // Account Receivable
//                        $cash_chart_of_account_info = ChartOfAccount::where('head_name', 'Account Receivable')->first();
//                        $chart_of_account_transaction_details = new ChartOfAccountTransactionDetail();
//                        $chart_of_account_transaction_details->warehouse_id = NULL;
//                        $chart_of_account_transaction_details->store_id = NULL;
//                        $chart_of_account_transaction_details->chart_of_account_transaction_id = $chart_of_account_transactions_insert_id;
//                        $chart_of_account_transaction_details->chart_of_account_id = $cash_chart_of_account_info->id;
//                        $chart_of_account_transaction_details->chart_of_account_number = $cash_chart_of_account_info->head_code;
//                        $chart_of_account_transaction_details->chart_of_account_name = 'Account Receivable';
//                        $chart_of_account_transaction_details->chart_of_account_parent_name = $cash_chart_of_account_info->parent_head_name;
//                        $chart_of_account_transaction_details->chart_of_account_type = $cash_chart_of_account_info->head_type;
//                        $chart_of_account_transaction_details->debit = NULL;
//                        $chart_of_account_transaction_details->credit = $request->initial_due;
//                        $chart_of_account_transaction_details->description = 'Initial Due';
//                        $chart_of_account_transaction_details->year = $year;
//                        $chart_of_account_transaction_details->month = $month;
//                        $chart_of_account_transaction_details->transaction_date = $date;
//                        $chart_of_account_transaction_details->transaction_date_time = $date_time;
//                        $chart_of_account_transaction_details->save();
//                    }
//                }

                $response = APIHelpers::createAPIResponse(false,201,'Customer Added Successfully.',null);
                return response()->json($response,201);
            }else{
                $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
                return response()->json($response,500);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
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
            //return $e->getMessage();
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
            //$customer->customer_type = $request->customer_type;
            $customer->name = $request->name;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->address = $request->address;
            $customer->initial_due = $request->initial_due;
            $image = $request->file('nid_front');
            //dd($image);
            if (isset($image)) {
                //make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                // delete old image.....
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->nid_front))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->nid_front);

                }

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->nid_back))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->nid_back);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->image))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

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
                if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->bank_detail_image))
                {
                    Storage::disk('public')->delete('uploads/suppliers/'.$supplier->bank_detail_image);

                }

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

                // update image db
                $customer->bank_detail_image = $imagename;

            }else{
                $customer->bank_detail_image = Customer::where('id',$customer->id)->pluck('bank_detail_image')->first();
            }
            $customer->save();
            $update_customer = $customer->save();

            if($update_customer){
                $chart_of_account = ChartOfAccount::where('name_code',$customer->code)->first();
                $chart_of_account->head_name=$request->name.'-'.$customer->code;
                $chart_of_account->save();

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
}
