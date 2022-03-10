<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Employee;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Supplier;
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

class SupplierController extends Controller
{
    // supplier
    public function supplierActiveList(){
        try {
            $suppliers = DB::table('suppliers')
                ->select('id','name','phone','email','address','nid_front','nid_back','status')
                ->where('status',1)
                ->orderBy('id','desc')
                ->get();

            if($suppliers === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Suppliers Found.',null);
                return response()->json($response,404);
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$suppliers);
            return response()->json($response,200);

        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function supplierList(){
        try {
            $suppliers = DB::table('suppliers')
                ->select('id','code','name','shop_name','phone','email','address','initial_due','current_total_due','nid_front','nid_back','image','bank_detail_image','note','status')
                ->orderBy('id','desc')
                ->get();

            if($suppliers === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Suppliers Found.',null);
                return response()->json($response,404);
            }

            $supplier_arr = [];
            foreach ($suppliers as $supplier) {
                $nested_data['id'] = $supplier->id;
                $nested_data['shop_name'] = $supplier->shop_name;
                $nested_data['code'] = $supplier->code;
                $nested_data['name'] = $supplier->name;
                $nested_data['phone'] = $supplier->phone;
                $nested_data['email'] = $supplier->email;
                $nested_data['address'] = $supplier->address;
                $nested_data['initial_due'] = $supplier->initial_due;
                $nested_data['current_total_due'] = $supplier->current_total_due;
                $nested_data['nid_front'] = $supplier->nid_front;
                $nested_data['nid_back'] = $supplier->nid_back;
                $nested_data['image'] = $supplier->image;
                $nested_data['bank_detail_image'] = $supplier->bank_detail_image;
                $nested_data['note'] = $supplier->note;
                $nested_data['status'] = $supplier->status;

                array_push($supplier_arr, $nested_data);
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$supplier_arr);
            return response()->json($response,200);

        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function supplierCreate(Request $request){

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

            $get_supplier_code = Supplier::latest('id','desc')->pluck('code')->first();
            if(!empty($get_supplier_code)){
                $get_supplier_code_after_replace = str_replace("SC-","",$get_supplier_code);
                $supplier_code = $get_supplier_code_after_replace+1;
            }else{
                $supplier_code = 1;
            }
            $final_supplier_code = 'SC-'.$supplier_code;

            $supplier = new Supplier();
            $supplier->name = $request->name;
            $supplier->shop_name = $request->shop_name;
            $supplier->code = $final_supplier_code;
            $supplier->phone = $request->phone;
            $supplier->email = $request->email;
            $supplier->address = $request->address;
            $supplier->initial_due = $request->initial_due;
            $supplier->current_total_due = $request->initial_due;
            $supplier->note = $request->note;

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
                $supplier->nid_front = $imagename;

            }else{
                $supplier->nid_front = 'default.png';
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
                $supplier->nid_back = $imagename;

            }else{
                $supplier->nid_back = 'default.png';
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
                $supplier->image = $imagename;

            }else{
                $supplier->image = 'default.png';
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
                $supplier->bank_detail_image = $imagename;

            }else{
                $supplier->bank_detail_image = 'default.png';
            }

            $supplier->save();
            $insert_id = $supplier->id;

            if($insert_id){
                $account = DB::table('chart_of_accounts')
                    ->where('head_level',3)
                    ->where('head_code', 'like', '20101%')
                    ->Orderby('created_at', 'desc')
                    ->limit(1)
                    ->first();

                if(!empty($account)){
                    $head_code=$account->head_code+1;
                }else{
                    $head_code="2010100001";
                }
                $head_name = $request->name.'-'.$final_supplier_code;

                $parent_head_name = 'Account Payable';
                $head_level = 3;
                $head_type = 'L';

                $coa = new ChartOfAccount();
                $coa->head_debit_or_credit  = 'Cr';
                $coa->head_code             = $head_code;
                $coa->head_name             = $head_name;
                $coa->name_code             = $final_supplier_code;
                $coa->parent_head_name      = $parent_head_name;
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



                // supplier initial due
                if($request->initial_due > 0){
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

                    // Cash In Hand account
                    $supplier_account = ChartOfAccount::where('head_name','Cash In Hand')->first();

                    // coa
                    $description = 'Opening Balance of '.$supplier_account->head_name;
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 8, $final_voucher_no, 'Opening Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $coa->id, $coa->head_code, $coa->head_name, $coa->parent_head_name, $coa->head_type, $request->initial_due, NULL, $description, 'Approved');
                    chartOfAccountTransactionDetails($insert_id, NULL, $user_id, 8, $final_voucher_no, 'Opening Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $supplier_account->id, $supplier_account->head_code, $supplier_account->head_name, $supplier_account->parent_head_name, $supplier_account->head_type, NULL, $request->initial_due, $description, 'Approved');
                }


                $response = APIHelpers::createAPIResponse(false,201,'Supplier Added Successfully.',null);
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

    public function supplierDetails(Request $request){
        try {
            $check_exists_supplier = DB::table("suppliers")->where('id',$request->supplier_id)->pluck('id')->first();
            if($check_exists_supplier == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Supplier Found.',null);
                return response()->json($response,404);
            }

            $supplier = DB::table("suppliers")->where('id',$request->supplier_id)->latest()->first();
            $response = APIHelpers::createAPIResponse(false,200,'',$supplier);
            return response()->json($response,200);
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function supplierUpdate(Request $request){
        try {
            $check_exists_supplier = DB::table("suppliers")->where('id',$request->supplier_id)->pluck('id')->first();
            if($check_exists_supplier == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Supplier Found.',null);
                return response()->json($response,404);
            }

            // required
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required',
                'name' => 'required',
                'phone' => 'required',
                'status'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $supplier = Supplier::find($request->supplier_id);
            $previous_initial_due = $supplier->current_total_due;
            $previous_current_total_due = $supplier->current_total_due;
            if($previous_initial_due > $request->initial_due){
                $increase_current_total_due = $previous_initial_due - $request->initial_due;
                $update_current_total_due = $previous_current_total_due + $increase_current_total_due;
            }else{
                $decrease_current_total_due = $request->initial_due - $previous_initial_due;
                $update_current_total_due = $previous_current_total_due + $decrease_current_total_due;
            }

            $supplier->name = $request->name;
            $supplier->shop_name = $request->shop_name;
            $supplier->phone = $request->phone;
            $supplier->email = $request->email;
            $supplier->address = $request->address;
            $supplier->status = $request->status;
            $supplier->initial_due = $request->initial_due;
            $supplier->current_total_due = $update_current_total_due;
            $supplier->note = $request->note;

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

                //resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

                // update image db
                $supplier->nid_front = $imagename;

            }else{
                $supplier->nid_front = Supplier::where('id',$request->supplier_id)->pluck('nid_front')->first();
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

                //            resize image for hospital and upload
                //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
                $proImage = Image::make($image)->save($image->getClientOriginalExtension());
                Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

                // update image db
                $supplier->nid_back = $imagename;

            }else{
                $supplier->nid_back = Supplier::where('id',$request->supplier_id)->pluck('nid_back')->first();
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
                $supplier->image = $imagename;

            }else{
                $supplier->image = Supplier::where('id',$request->supplier_id)->pluck('image')->first();
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
                $supplier->bank_detail_image = $imagename;

            }else{
                $supplier->bank_detail_image = Supplier::where('id',$request->supplier_id)->pluck('bank_detail_image')->first();
            }

            $update_supplier = $supplier->save();

            if($update_supplier){
                $coa = ChartOfAccount::where('name_code',$supplier->code)->first();
                $coa->head_name=$request->name.'-'.$supplier->code;
                $coa->save();

                // supplier initial due
                if( ($previous_initial_due == 0) && ($request->initial_due > 0) ) {
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

                    $supplier_account = ChartOfAccount::where('head_name','Cash In Hand')->first();
                    $description = 'Opening Balance of '.$supplier_account->head_name;
                    chartOfAccountTransactionDetails($request->supplier_id, NULL, $user_id, 8, $final_voucher_no, 'Opening Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $coa->id, $coa->head_code, $coa->head_name, $coa->parent_head_name, $coa->head_type, $request->initial_due, NULL, $description, 'Approved');
                    chartOfAccountTransactionDetails($request->supplier_id, NULL, $user_id, 8, $final_voucher_no, 'Opening Balance', $date, $date_time, $year, $month, NULL, NULL, 1, NULL, NULL, NULL, $supplier_account->id, $supplier_account->head_code, $supplier_account->head_name, $supplier_account->parent_head_name, $supplier_account->head_type, NULL, $request->initial_due, $description, 'Approved');
                }elseif( $request->initial_due !== $previous_initial_due ){

                    $chart_of_account_name = $supplier->name.'-'.$supplier->code;
                    $supplier_opening_balance = ChartOfAccountTransactionDetail::where('payment_type_id',8)
                        ->where('chart_of_account_name',$chart_of_account_name)
                        ->first();
                    if(!empty($supplier_opening_balance)){
                        $supplier_opening_balance->debit = $request->initial_due;
                        $supplier_opening_balance->credit = NULL;
                        $supplier_opening_balance->save();
                    }

                    // Cash In Hand account
                    $cash_in_hand_opening_balance = ChartOfAccountTransactionDetail::where('payment_type_id',8)
                        ->where('chart_of_account_name','Cash In Hand')
                        ->first();
                    if(!empty($cash_in_hand_opening_balance)){
                        $cash_in_hand_opening_balance->debit = NULL;
                        $cash_in_hand_opening_balance->credit = $request->initial_due;
                        $cash_in_hand_opening_balance->save();
                    }
                }

                $response = APIHelpers::createAPIResponse(false,200,'Supplier Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Supplier Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function supplierDelete(Request $request){
        try {
            $check_exists_supplier = DB::table("suppliers")->where('id',$request->supplier_id)->pluck('id')->first();
            if($check_exists_supplier == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Supplier Found.',null);
                return response()->json($response,404);
            }

            $soft_delete_supplier = Supplier::find($request->supplier_id);
            $soft_delete_supplier->status=0;
            $affected_row = $soft_delete_supplier->update();
            if($affected_row)
            {
                $response = APIHelpers::createAPIResponse(false,200,'Supplier Successfully Soft Deleted.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Supplier Updated Failed.',null);
                return response()->json($response,400);
            }
        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }


    public function supplierImage(Request $request)
    {
        $supplier=Supplier::find($request->supplier_id);
        //dd($supplier);
        $image = $request->file('nid_image');
        //dd($image);
        if (isset($image)) {
            //make unique name for image
            $currentDate = Carbon::now()->toDateString();
            $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

            // delete old image.....
            if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->nid))
            {
                Storage::disk('public')->delete('uploads/suppliers/'.$supplier->nid);

            }

//            resize image for hospital and upload
            //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
            $proImage = Image::make($image)->save($image->getClientOriginalExtension());
            Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);

            // update image db
            $supplier->nid = $imagename;
            $supplier->update();

            $success['supplier'] = $supplier;
            return response()->json(['response' => $success], 200);

        }else{
            return response()->json(['response'=>'failed'], 400);
        }

    }

//    public function supplierImage(Request $request)
//    {
//        //return response()->json(['success'=>true,'response' => 'sdfsdf'], 200);
////        return response()->json(['success'=>true,'response' => $request->all()], 200);
//        $supplier=Supplier::find($request->supplier_id);
//        //dd($request->all());
//        //return response()->json(['success'=>true,'response' => $supplier], 200);
//        $image = $request->file('nid');
//        return response()->json(['success'=>true,'response' => $image], 200);
//        if (isset($image)) {
//            //make unique name for image
//            $currentDate = Carbon::now()->toDateString();
//            $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
//
//            // delete old image.....
//            if(Storage::disk('public')->exists('uploads/suppliers/'.$supplier->image))
//            {
//                Storage::disk('public')->delete('uploads/suppliers/'.$supplier->image);
//
//            }
//
////            resize image for hospital and upload
//            $proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
//            Storage::disk('public')->put('uploads/suppliers/'. $imagename, $proImage);
//
//            // update image db
//            $supplier->nid = $imagename;
//            $supplier->update();
//
//            //$success['supplier'] = $supplier;
//            //return response()->json(['response' => $success], $this-> successStatus);
//            $response = APIHelpers::createAPIResponse(false,200,'',$supplier);
//            return response()->json($response,200);
//
//        }else{
//            //return response()->json(['response'=>'failed'], $this-> failStatus);
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
//
//    }

    public function supplierCurrentTotalDueBySupplierId(Request $request){
        try {
            $current_total_due = supplierCurrentTotalDueByCustomerId($request->supplier_id);

            if($current_total_due !== 0){
                return response()->json(['success'=>true,'code' => 200,'data' => $current_total_due], 200);
            }else{
                return response()->json(['success'=>false,'code' => 400, 'message' => 'No Supplier Found.'], 400);
            }

        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function supplierDuePaid(Request $request){
//        try {
            $supplier_id = $request->supplier_id;
            $payment_type_id = $request->payment_type_id;
            $paid_amount = $request->paid_amount;
            $description = $request->description;

            $check_exists_supplier = DB::table("suppliers")->where('id',$supplier_id)->pluck('id')->first();
            if($check_exists_supplier == null){
                $response = APIHelpers::createAPIResponse(true,404,'No Supplier Found.',null);
                return response()->json($response,404);
            }

            // required
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required',
                'paid_amount' => 'required',
                'due_amount' => 'required',
                'payment_type_id' => 'required'
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $supplier = Supplier::find($supplier_id);
            $previous_current_total_due = $supplier->current_total_due;
            $update_current_total_due = $previous_current_total_due - $paid_amount;

            $supplier->current_total_due = $update_current_total_due;
            $affected_row = $supplier->save();

            if($affected_row){
                // posting
                $date = date('Y-m-d');
                $user_id = Auth::user()->id;
                $warehouse_id = NULL;
                $store_id = NULL;
                $month = date('m');
                $year = date('Y');
                $transaction_date_time = date('Y-m-d H:i:s');

                // Account Payable Account Info
                $account_payable_info = ChartOfAccount::where('head_name','Account Payable')->first();

                // Cash In Hand For Paid Amount
                $get_voucher_name = VoucherType::where('id', 1)->pluck('name')->first();
                $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id', 1)->latest()->pluck('voucher_no')->first();
                if (!empty($get_voucher_no)) {
                    $get_voucher_name_str = $get_voucher_name . "-";
                    $get_voucher = str_replace($get_voucher_name_str, "", $get_voucher_no);
                    $voucher_no = $get_voucher + 1;
                } else {
                    $voucher_no = 1000;
                }
                $final_voucher_no = $get_voucher_name . '-' . $voucher_no;
                // Cash In Hand Account Info
                $cash_chart_of_account_info = ChartOfAccount::where('head_name', 'Cash In Hand')->first();

                // Cheque Account Info
                $cheque_chart_of_account_info = ChartOfAccount::where('head_name', 'Cheque')->first();

                // supplier head
                $code = Supplier::where('id', $supplier_id)->pluck('code')->first();
                //$supplier_chart_of_account_info = ChartOfAccount::where('name_code', $code)->first();

                // Account Payable
                $description = $description ? $description : $account_payable_info->head_name . ' Debited For Due Amount Paid';
                chartOfAccountTransactionDetails(NULL, NULL, $user_id, 1, $final_voucher_no, 'Due Paid', $date, $transaction_date_time, $year, $month, NULL, NULL, $payment_type_id, NULL, NULL, NULL, $account_payable_info->id, $account_payable_info->head_code, $account_payable_info->head_name, $account_payable_info->parent_head_name, $account_payable_info->head_type, $paid_amount, NULL, $description, 'Approved');
                if($payment_type_id == '1') {
                    // Cash In Hand credit
                    $description = $description ? $description : $cash_chart_of_account_info->head_name . ' Credited For Due Amount Paid';
                    chartOfAccountTransactionDetails(NULL, NULL, $user_id, 1, $final_voucher_no, 'Due Paid', $date, $transaction_date_time, $year, $month, NULL, NULL, $payment_type_id, NULL, NULL, NULL, $cash_chart_of_account_info->id, $cash_chart_of_account_info->head_code, $cash_chart_of_account_info->head_name, $cash_chart_of_account_info->parent_head_name, $cash_chart_of_account_info->head_type, NULL, $paid_amount, $description, 'Approved');
                }

                if($payment_type_id == '2') {
                    // Cheque debit
                    $description = $description ? $description : $cheque_chart_of_account_info->head_name . ' Debited For Due Amount Paid';
                    chartOfAccountTransactionDetails(NULL, NULL, $user_id, 1, $final_voucher_no, 'Due Paid', $date, $transaction_date_time, $year, $month, NULL, NULL, $payment_type_id, NULL, NULL, NULL, $cheque_chart_of_account_info->id, $cheque_chart_of_account_info->head_code, $cheque_chart_of_account_info->head_name, $cheque_chart_of_account_info->parent_head_name, $cheque_chart_of_account_info->head_type, $paid_amount, NULL, $description, 'Approved');
                }

                $response = APIHelpers::createAPIResponse(false,200,'Supplier Updated Successfully.',null);
                return response()->json($response,200);
            }else{
                $response = APIHelpers::createAPIResponse(true,400,'Supplier Updated Failed.',null);
                return response()->json($response,400);
            }
//        } catch (\Exception $e) {
//            //return $e->getMessage();
//            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
//            return response()->json($response,500);
//        }
    }
}
