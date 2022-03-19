<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\ChartOfAccountTransaction;
use App\ChartOfAccountTransactionDetail;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\User;
use App\VoucherType;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AccountController extends Controller
{
    public $successStatus = 200;
    public $authStatus = 401;
    public $failStatus = 402;
    public $ExistsStatus = 403;
    public $validationStatus = 404;

    // voucher type
    public function voucherTypeList(){
        $voucher_types = DB::table('voucher_types')->select('id','name','voucher_prefix','status')->orderBy('id','desc')->get();

        if($voucher_types)
        {
            $success['voucher_type'] =  $voucher_types;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Voucher Type List Found!'], $this->failStatus);
        }
    }

    public function voucherTypeCreate(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:voucher_types,name',
            'voucher_prefix'=> 'required',
            'status'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }


        $voucherType = new VoucherType();
        $voucherType->name = $request->name;
        $voucherType->voucher_prefix = $request->voucher_prefix;
        $voucherType->status = $request->status;
        $voucherType->save();
        $insert_id = $voucherType->id;

        if($insert_id){
            return response()->json(['success'=>true,'response' => $voucherType], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'Voucher Type Not Created Successfully!'], $this->failStatus);
        }
    }

    public function voucherTypeEdit(Request $request){

        $validator = Validator::make($request->all(), [
            'voucher_type_id'=> 'required',
            'name' => 'required|unique:voucher_types,name,'.$request->voucher_type_id,
            'voucher_prefix'=> 'required',
            'status'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this->validationStatus);
        }

        $check_exists_voucher_type = DB::table("voucher_types")->where('id',$request->voucher_type_id)->pluck('id')->first();
        if($check_exists_voucher_type == null){
            return response()->json(['success'=>false,'response'=>'No Voucher Type Found!'], $this->failStatus);
        }

        $voucher_types = VoucherType::find($request->voucher_type_id);
        $voucher_types->name = $request->name;
        $voucher_types->voucher_prefix = $request->voucher_prefix;
        $voucher_types->status = $request->status;
        $update_voucher_type = $voucher_types->save();

        if($update_voucher_type){
            return response()->json(['success'=>true,'response' => $voucher_types], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'Voucher Type Not Created Successfully!'], $this->failStatus);
        }
    }

    public function voucherTypeDelete(Request $request){
        $check_exists_voucher_type = DB::table("voucher_types")->where('id',$request->voucher_type_id)->pluck('id')->first();
        if($check_exists_voucher_type == null){
            return response()->json(['success'=>false,'response'=>'No Voucher Type Found!'], $this->failStatus);
        }

        //$delete_party = DB::table("voucher_types")->where('id',$request->voucher_type_id)->delete();
        $soft_delete_voucher_type = VoucherType::find($request->voucher_type_id);
        $soft_delete_voucher_type->status=0;
        $affected_row = $soft_delete_voucher_type->update();
        if($affected_row)
        {
            return response()->json(['success'=>true,'response' => 'Voucher Type Successfully Soft Deleted!'], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Voucher Type Deleted!'], $this->failStatus);
        }
    }

    public function chartOfAccountList(){

        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','user_bank_account_no','head_level','is_active','is_transaction','is_general_ledger','head_type')
            ->get();


        if($chart_of_accounts)
        {
            $success['chart_of_accounts'] =  $chart_of_accounts;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts List Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountListByName(Request $request){

        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','user_bank_account_no','head_level','is_active','is_transaction','is_general_ledger','head_type');

        if($request->head_name == ''){
            $chart_of_accounts->where('head_level',0);
        }else{
            $chart_of_accounts->where('parent_head_name',$request->head_name);
        }
        $chart_of_accounts->get();

        if($chart_of_accounts)
        {
            $success['chart_of_accounts'] =  $chart_of_accounts;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts List Found!'], $this->failStatus);
        }
    }

    public function child($head_name){
        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select(
                'id',
                'head_code',
                'head_name',
                'parent_head_name',
                'user_bank_account_no',
                'head_level',
                'is_active',
                'is_transaction',
                'is_general_ledger',
                'head_type'
            )
            ->where('parent_head_name',$head_name)
            //->orderBy('id','desc')
            ->get();
    }

    public function chartOfAccountRecursiveList(Request $request){

        $result = Array();
        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select(
                'id',
                'head_code',
                'head_name',
                'parent_head_name',
                'user_bank_account_no',
                'head_level',
                'is_active',
                'is_transaction',
                'is_general_ledger',
                'head_type'
            )
            ->where('head_level',0)
            ->get();

        foreach ($chart_of_accounts as $chart_of_account){

            $coa['id'] = $chart_of_account->id;
            $coa['head_code'] = $chart_of_account->head_code;
            $coa['head_name'] = $chart_of_account->head_name;
            $coa['parent_head_name'] = $chart_of_account->parent_head_name;
            $coa['head_type'] = $chart_of_account->head_type;
            $coa['head_level'] = $chart_of_account->head_level;
            $coa['is_active'] = $chart_of_account->is_active;
            $coa['is_transaction'] = $chart_of_account->is_transaction;
            $coa['is_general_ledger'] = $chart_of_account->is_general_ledger;
            $coa['user_bank_account_no'] = $chart_of_account->user_bank_account_no;

            $child = ChartOfAccount::where('parent_head_name',$chart_of_account->head_name)
                //->where('parent_head_name',$chart_of_account->head_code)
                ->get();

            if(count($child) > 0){
                $this->child($chart_of_account->head_name);
            }

            array_push($result, $coa);
        }

        return response()->json(['success'=> $result], $this-> successStatus);
    }

    public function chartOfAccountActiveList(){
        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','head_type','head_level','is_active','is_transaction','is_general_ledger')
            ->where('is_active',1)
            ->get();

        if($chart_of_accounts)
        {
            $success['chart_of_accounts'] =  $chart_of_accounts;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts List Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountIsTransactionList(){
        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','head_type','head_level','is_active','is_transaction','is_general_ledger')
            ->where('is_transaction',1)
            ->get();

        if($chart_of_accounts)
        {
            $success['chart_of_accounts'] =  $chart_of_accounts;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts List Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountIsCashBookList(){
        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','head_type','head_level','is_active','is_transaction','is_general_ledger')
            ->where('is_general_ledger',1)
            ->get();

        if($chart_of_accounts)
        {
            $success['chart_of_accounts'] =  $chart_of_accounts;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts List Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountIsGeneralLedgerList(){
        $chart_of_accounts = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','head_type','head_level','is_active','is_transaction','is_general_ledger')
            ->where('is_general_ledger',1)
            ->get();

        if($chart_of_accounts)
        {
            $success['chart_of_accounts'] =  $chart_of_accounts;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts List Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'head_name'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $chart_of_account_details = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','head_type','head_level','is_active','is_transaction','is_general_ledger')
            ->where('head_name',$request->head_name)
            ->orderBy('id','desc')
            ->get();

        if($chart_of_account_details)
        {
            $success['chart_of_account__details'] =  $chart_of_account_details;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts Details Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountGenerateHeadCode(Request $request){
        $validator = Validator::make($request->all(), [
            'head_name'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $n = 1;
        $chart_of_account_head_code = DB::table('chart_of_accounts')
            ->where('parent_head_name',$request->head_name)
            ->latest('id')
            ->pluck('head_code')
            ->first();

        if($chart_of_account_head_code != NULL){
            $head_code = $chart_of_account_head_code + 1;
        }else{
            $current_head_code = DB::table('chart_of_accounts')
                ->where('head_name',$request->head_name)
                ->latest('id')
                ->pluck('head_code')
                ->first();

            if($current_head_code){
                $head_code = $current_head_code . "0" . $n;
            }
        }

        if($head_code)
        {

            $success['head_code'] =  $head_code;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Head Code Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountParentHeadDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'parent_head_name'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $chart_of_account_parent_head_details = DB::table('chart_of_accounts')
            ->select('id','head_code','head_name','parent_head_name','head_type','head_level','is_active','is_transaction','is_general_ledger')
            ->where('head_name',$request->parent_head_name)
            ->orderBy('id','desc')
            ->get();

        if($chart_of_account_parent_head_details)
        {
            $success['chart_of_account_parent_head_details'] =  $chart_of_account_parent_head_details;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts Parent Head Details Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountCreate(Request $request){

        $validator = Validator::make($request->all(), [
            'head_code'=> 'required',
            'head_name' => 'required|unique:chart_of_accounts,head_name',
            'parent_head_name'=> 'required',
            'head_type'=> 'required',
            'head_level'=> 'required',
            'is_active'=> 'required',
            'is_transaction'=> 'required',
            'is_general_ledger'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $n = 1;
        $chart_of_account_head_code = DB::table('chart_of_accounts')
            ->where('parent_head_name',$request->parent_head_name)
            ->latest('id')
            ->pluck('head_code')
            ->first();

        if($chart_of_account_head_code != NULL){
            $head_code = $chart_of_account_head_code + 1;
        }else{
            $current_head_code = DB::table('chart_of_accounts')
                ->where('head_name',$request->parent_head_name)
                ->latest('id')
                ->pluck('head_code')
                ->first();

            if($current_head_code){
                $head_code = $current_head_code . "0" . $n;
            }
        }


        $ref_id = NULL;
        if($request->parent_head_name == 'Account Receivable'){
            $parties = new Party();
            $parties->type = 'customer';
            $parties->customer_type = 'POS Sale';
            $parties->name = $request->name;
            $parties->slug = Str::slug($request->name);
            $parties->phone = '01700000000';
            $parties->email = NULL;
            $parties->address = NULL;
            $parties->status = 1;
            $parties->save();
            $insert_id = $parties->id;

            if($insert_id){
                $ref_id = $insert_id;
                if($request->type == 'customer'){
                    $user_data['name'] = $request->name;
                    $user_data['email'] = NULL;
                    $user_data['phone'] = '01700000000';
                    $user_data['password'] = Hash::make(123456);
                    $user_data['party_id'] = $insert_id;
                    $user = User::create($user_data);
                    // first create customer role, then bellow code enable
                    $user->assignRole('customer');
                }
            }
        }

        if($request->parent_head_name == 'Account Payable'){
            $parties = new Party();
            $parties->type = 'supplier';
            $parties->customer_type = NULL;
            $parties->name = $request->head_name;
            $parties->slug = Str::slug($request->head_name);
            $parties->phone = '01700000000';
            $parties->email = NULL;
            $parties->address = NULL;
            $parties->status = 1;
            $parties->save();
            $insert_id = $parties->id;

            if($insert_id){
                $ref_id = $insert_id;
            }
        }


        $chart_of_accounts = new ChartOfAccount();
        $chart_of_accounts->head_code = $head_code;
        $chart_of_accounts->head_name = $request->head_name;
        $chart_of_accounts->parent_head_name = $request->parent_head_name;
        $chart_of_accounts->head_type = $request->head_type;
        $chart_of_accounts->head_level = $request->head_level;
        $chart_of_accounts->is_active = $request->is_active;
        $chart_of_accounts->is_transaction = $request->is_transaction;
        $chart_of_accounts->is_general_ledger = $request->is_general_ledger;
        $chart_of_accounts->ref_id = $ref_id;
        $chart_of_accounts->user_bank_account_no = NULL;
        $chart_of_accounts->save();
        $insert_id = $chart_of_accounts->id;

        if($insert_id){
            return response()->json(['success'=>true,'response' => $chart_of_accounts], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'Chart Of Accounts Not Created Successfully!'], $this->failStatus);
        }
    }

    public function chartOfAccountEdit(Request $request){

        $validator = Validator::make($request->all(), [
            'chart_of_account_id'=> 'required',
            'is_active'=> 'required',
            'is_transaction'=> 'required',
            'is_general_ledger'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this->validationStatus);
        }

        $check_exists_chart_of_account = DB::table("chart_of_accounts")->where('id',$request->chart_of_account_id)->pluck('id')->first();
        if($check_exists_chart_of_account == null){
            return response()->json(['success'=>false,'response'=>'No Chart Of Account Found!'], $this->failStatus);
        }

        $chart_of_accounts = ChartOfAccount::find($request->chart_of_account_id);
        //$chart_of_accounts->head_code = $request->head_code;
        //$chart_of_accounts->head_name = $request->head_name;
        //$chart_of_accounts->parent_head_name = $request->parent_head_name;
        //$chart_of_accounts->head_type = $request->head_type;
        //$chart_of_accounts->head_level = $request->head_level;
        $chart_of_accounts->is_active = $request->is_active;
        $chart_of_accounts->is_transaction = $request->is_transaction;
        $chart_of_accounts->is_general_ledger = $request->is_general_ledger;
        $update_chart_of_account = $chart_of_accounts->save();

        if($update_chart_of_account){
            return response()->json(['success'=>true,'response' => $chart_of_accounts], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'Chart Of Account Not Created Successfully!'], $this->failStatus);
        }
    }

    public function chartOfAccountDelete(Request $request){
        $check_exists_chart_of_account = DB::table("chart_of_accounts")->where('id',$request->chart_of_account_id)->pluck('id')->first();
        if($check_exists_chart_of_account == null){
            return response()->json(['success'=>false,'response'=>'No chart_of_account Found!'], $this->failStatus);
        }

        $soft_delete_chart_of_account = ChartOfAccount::find($request->chart_of_account_id);
        $soft_delete_chart_of_account->is_active=0;
        $affected_row = $soft_delete_chart_of_account->update();
        if($affected_row)
        {
            return response()->json(['success'=>true,'response' => 'Chart Of Account Successfully Soft Deleted!'], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Account Deleted!'], $this->failStatus);
        }
    }

    public function chartOfAccountTransactionList(){
        $user = User::find(Auth::id());
        $user_role = $user->getRoleNames()[0];
        $warehouse_id = $user->warehouse_id;
        $store_id = $user->store_id;

        $chart_of_account_transactions = DB::table("chart_of_account_transaction_details")
            ->leftJoin('voucher_types','chart_of_account_transaction_details.voucher_type_id','=','voucher_types.id')
            ->select(
                'voucher_types.name as voucher_type_name',
                'chart_of_account_transaction_details.voucher_no',
                'chart_of_account_transaction_details.chart_of_account_name',
                'chart_of_account_transaction_details.debit',
                'chart_of_account_transaction_details.credit',
                'chart_of_account_transaction_details.description',
                'chart_of_account_transaction_details.transaction_date',
                'chart_of_account_transaction_details.transaction_date_time'
            );

        if( ($user_role !== 'Super Admin') && ($store_id != null) ){
            $chart_of_account_transactions->where('chart_of_account_transaction_details.store_id','=',$store_id);
        }

        if( ($user_role !== 'Super Admin') && ($warehouse_id != null) ){
            $chart_of_account_transactions->where('chart_of_account_transaction_details.warehouse_id','=',$warehouse_id);
        }

        $product_purchase_data = $chart_of_account_transactions->latest('chart_of_account_transaction_details.id','desc')->paginate(12);


        if($product_purchase_data === null){
            $response = APIHelpers::createAPIResponse(true,404,'No Transactions Found.',null);
            return response()->json($response,404);
        }else{
            $response = APIHelpers::createAPIResponse(false,200,'',$product_purchase_data);
            return response()->json($response,200);
        }
    }

    public function chartOfAccountTransactionDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'chart_of_account_transaction_id'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $chart_of_account_transaction_details = DB::table('chart_of_account_transaction_details')
            ->select(
                'chart_of_account_transaction_details.id',
                'chart_of_account_transaction_details.chart_of_account_transaction_id',
                'chart_of_account_transaction_details.chart_of_account_id',
                'chart_of_account_transaction_details.chart_of_account_number',
                'chart_of_account_transaction_details.chart_of_account_name',
                'chart_of_account_transaction_details.chart_of_account_parent_name',
                'chart_of_account_transaction_details.chart_of_account_type',
                'chart_of_account_transaction_details.debit',
                'chart_of_account_transaction_details.credit',
                'chart_of_account_transaction_details.description',
                'chart_of_account_transaction_details.transaction_date',
                'chart_of_account_transaction_details.transaction_date_time'
            )
            ->where('chart_of_account_transaction_details.chart_of_account_transaction_id',$request->chart_of_account_transaction_id)
            ->orderBy('chart_of_account_transaction_details.id','desc')
            ->get();

        if($chart_of_account_transaction_details)
        {
            $success['chart_of_account_transaction_details'] =  $chart_of_account_transaction_details;
            return response()->json(['success'=>true,'response' => $success], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'No Chart Of Accounts Transaction Details Found!'], $this->failStatus);
        }
    }

    public function chartOfAccountTransactionCreate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'voucher_type_id'=> 'required',
                'date'=> 'required',
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createAPIResponse(true,400,$validator->errors(),null);
                return response()->json($response,400);
            }

            $user_id = Auth::user()->id;
            $month = date('m', strtotime($request->date));
            $year = date('Y', strtotime($request->date));
            $transaction_date_time = date('Y-m-d H:i:s');

            $get_voucher_name = VoucherType::where('id',$request->voucher_type_id)->pluck('name')->first();
            $get_voucher_no = ChartOfAccountTransaction::where('voucher_type_id',$request->voucher_type_id)->latest()->pluck('voucher_no')->first();
            if(!empty($get_voucher_no)){
                $get_voucher_name_str = $get_voucher_name."-";
                $get_voucher = str_replace($get_voucher_name_str,"",$get_voucher_no);
                $voucher_no = $get_voucher+1;
            }else{
                $voucher_no = 8000;
            }
            $final_voucher_no = $get_voucher_name.'-'.$voucher_no;

            $warehouse_id = $request->warehouse_id ? $request->warehouse_id : NULL;
            $store_id = $request->store_id ? $request->store_id : NULL;

            $transactions = json_decode($request->transactions);
            foreach ($transactions as $data){
                $debit = NULL;
                $credit = NULL;
                $debit_or_credit = $data->debit_or_credit;
                $debit_amount = $data->amount;
                $credit_amount = $data->amount;
                $chart_of_account_name = $data->chart_of_account_name;
                $description = $data->description;

                $chart_of_account_info = ChartOfAccount::where('head_name',$chart_of_account_name)->first();
                if($debit_or_credit == 'debit'){
                    $debit = $debit_amount;
                }
                if($debit_or_credit == 'credit'){
                    $credit = $credit_amount;
                }

                chartOfAccountTransactionDetails(NULL, NULL, $user_id, 1, $final_voucher_no, 'Sales', $get_voucher_name, $transaction_date_time, $year, $month, $warehouse_id, $store_id, 1, NULL, NULL, NULL, $chart_of_account_info->id, $chart_of_account_info->head_code, $chart_of_account_info->head_name, $chart_of_account_info->parent_head_name, $chart_of_account_info->head_type, $debit, $credit, $description, 'Approved');
            }

            $response = APIHelpers::createAPIResponse(false,201,'Product Category Added Successfully.',null);
            return response()->json($response,201);
        } catch (\Exception $e) {
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }

    public function chartOfAccountTransactionEdit(Request $request){

        $validator = Validator::make($request->all(), [
            'chart_of_account_transaction_id'=> 'required',
            'voucher_type_id'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $user_id = Auth::user()->id;

        $transaction_date = $request->date;
        $month = date('m', strtotime($request->date));
        $year = date('Y', strtotime($request->date));
        $transaction_date_time = date('Y-m-d H:i:s');

        if($request->store_id != 0){
            $store_id = $request->store_id;
        }else{
            $store_id = NULL;
        }

        $chart_of_account_transactions = ChartOfAccountTransaction::find($request->chart_of_account_transaction_id);
        $chart_of_account_transactions->user_id = $user_id;
        $chart_of_account_transactions->store_id = $store_id;
        $chart_of_account_transactions->voucher_type_id = $request->voucher_type_id;
        $chart_of_account_transactions->transaction_date = $transaction_date;
        $chart_of_account_transactions->transaction_date_time = $transaction_date_time;
        $chart_of_account_transactions->save();
        $insert_id = $chart_of_account_transactions->id;

        if($insert_id){
            foreach ($request->transactions as $data){

                $debit = NULL;
                $credit = NULL;
                $debit_or_credit = $data['debit_or_credit'];
                if($debit_or_credit == 'debit'){
                    $debit = $data['amount'];
                }
                if($debit_or_credit == 'credit'){
                    $credit = $data['amount'];
                }

                $chart_of_account_info = ChartOfAccount::where('head_name',$data['chart_of_account_name'])->first();

                $chart_of_account_transaction_details = ChartOfAccountTransactionDetail::find($data['chart_of_account_transaction_detail_id']);
                $chart_of_account_transaction_details->chart_of_account_id = $chart_of_account_info->id;
                $chart_of_account_transaction_details->chart_of_account_number = $chart_of_account_info->head_code;
                $chart_of_account_transaction_details->chart_of_account_name = $data['chart_of_account_name'];
                $chart_of_account_transaction_details->chart_of_account_parent_name = $chart_of_account_info->parent_head_name;
                $chart_of_account_transaction_details->chart_of_account_type = $chart_of_account_info->head_type;
                $chart_of_account_transaction_details->debit = $debit;
                $chart_of_account_transaction_details->credit = $credit;
                $chart_of_account_transaction_details->description = $data['description'];
                $chart_of_account_transaction_details->year = $year;
                $chart_of_account_transaction_details->month = $month;
                $chart_of_account_transaction_details->transaction_date = $transaction_date;
                $chart_of_account_transaction_details->transaction_date_time = $transaction_date_time;
                $chart_of_account_transaction_details->save();
            }
            return response()->json(['success'=>true,'response' => $chart_of_account_transactions], $this->successStatus);
        }else{
            return response()->json(['success'=>false,'response'=>'Chart Of Account Transactions Not Updated Successfully!'], $this->failStatus);
        }
    }

    public function trialBalanceReport(Request $request){
        $validator = Validator::make($request->all(), [
            'from_date'=> 'required',
            'to_date'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $warehouse_id = $request->warehouse_id ? $request->warehouse_id : NULL;
        $store_id = $request->store_id ? $request->store_id : NULL;

        $trial_balance_report = trial_balance_report($request->from_date, $request->to_date, $warehouse_id, $store_id, false);
        return response()->json(['success'=>true,'response' => $trial_balance_report], $this->successStatus);
    }

    public function ledger(Request $request){
        $validator = Validator::make($request->all(), [
            'chart_of_account_name'=> 'required',
            'from_date'=> 'required',
            'to_date'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $warehouse_id = $request->warehouse_id;
        $store_id = $request->store_id;
        $chart_of_account_name = $request->chart_of_account_name;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $gl_pre_valance = DB::table('chart_of_account_transaction_details')
            ->select('chart_of_account_transaction_details.chart_of_account_name', DB::raw('SUM(chart_of_account_transaction_details.debit) as debit, SUM(chart_of_account_transaction_details.credit) as credit'));

        $gl_pre_valance->where('chart_of_account_transaction_details.transaction_date', '<',$from_date)
                            ->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name);

        if( ($warehouse_id !== '') && ($store_id !== '') ){
            $gl_pre_valance->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id)
                                ->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        if($warehouse_id !== ''){
            $gl_pre_valance->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }

        if($store_id !== ''){
            $gl_pre_valance->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        $gl_pre_valance_data = $gl_pre_valance->groupBy('chart_of_account_transaction_details.chart_of_account_name')->first();

        $PreBalance=0;
        $preDebCre = 'De/Cr';
        $pre_debit = 0;
        $pre_credit = 0;
        if(!empty($gl_pre_valance_data))
        {
            $pre_debit = $gl_pre_valance_data->debit == NULL ? 0 : $gl_pre_valance_data->debit;
            $pre_credit = $gl_pre_valance_data->credit == NULL ? 0 : $gl_pre_valance_data->credit;
            if($pre_debit > $pre_credit)
            {
                $PreBalance = $pre_debit - $pre_credit;
                $preDebCre = 'De';
            }else{
                $PreBalance = $pre_credit - $pre_debit;
                $preDebCre = 'Cr';
            }
        }

        $chart_of_account_transaction = DB::table("chart_of_account_transaction_details")
            ->leftJoin('voucher_types','chart_of_account_transaction_details.voucher_type_id','=','voucher_types.id')
            ->select(
                'voucher_types.name as voucher_type_name',
                'chart_of_account_transaction_details.voucher_no',
                'chart_of_account_transaction_details.debit',
                'chart_of_account_transaction_details.credit',
                'chart_of_account_transaction_details.description',
                'chart_of_account_transaction_details.transaction_date_time'
            );

        $chart_of_account_transaction->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name);

        if(($from_date !== null) && ($to_date !== null)){
            $chart_of_account_transaction->where('chart_of_account_transaction_details.transaction_date','>=',$from_date)
                                            ->where('chart_of_account_transaction_details.transaction_date','<=',$to_date);
        }

        if( ($warehouse_id !== '') && ($store_id !== '') ){
            $chart_of_account_transaction->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id)
                ->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        if($warehouse_id !== ''){
            $chart_of_account_transaction->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }

        if($store_id !== ''){
            $chart_of_account_transaction->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        $chart_of_account_transaction->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name);

        $chart_of_account_transaction_data = $chart_of_account_transaction->get();

        $ledger_data = [
            'chart_of_account_transaction' => $chart_of_account_transaction_data,
            'PreBalance' => $PreBalance,
            'preDebCre' => $preDebCre,
            'pre_debit' => $pre_debit,
            'pre_credit' => $pre_credit,
            'chart_of_account_name' => $chart_of_account_name,
            'head_debit_or_credit' => get_head_debit_or_credit($chart_of_account_name),
            'from_date' => $from_date,
            'to_date' => $to_date,
        ];
        return response()->json(['success'=>true,'response' => $ledger_data], $this->successStatus);

    }

    public function cashBookReport(Request $request){
        $validator = Validator::make($request->all(), [
            'chart_of_account_name'=> 'required',
            'from_date'=> 'required',
            'to_date'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $warehouse_id = $request->warehouse_id;
        $store_id = $request->store_id;
        $chart_of_account_name = $request->chart_of_account_name;
        $from_date = $request->from_date;
        $to_date = $request->to_date;


        $gl_pre_valance_data = DB::table('chart_of_account_transaction_details')
            ->select('chart_of_account_transaction_details.chart_of_account_name', DB::raw('SUM(chart_of_account_transaction_details.debit) as debit, SUM(chart_of_account_transaction_details.credit) as credit'));

        $gl_pre_valance_data->where('chart_of_account_transaction_details.transaction_date', '<',$from_date)
            ->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name);

        if( ($warehouse_id !== '') && ($store_id !== '') ){
            $gl_pre_valance_data->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id)
                ->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        if($warehouse_id !== ''){
            $gl_pre_valance_data->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }

        if($store_id !== ''){
            $gl_pre_valance_data->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        $gl_pre_valance_data->groupBy('chart_of_account_transaction_details.chart_of_account_name')->first();

        $PreBalance=0;
        $preDebCre = 'De/Cr';
        $pre_debit = 0;
        $pre_credit = 0;
        if(!empty($gl_pre_valance_data))
        {
            $pre_debit = $gl_pre_valance_data->debit == NULL ? 0 : $gl_pre_valance_data->debit;
            $pre_credit = $gl_pre_valance_data->credit == NULL ? 0 : $gl_pre_valance_data->credit;
            if($pre_debit > $pre_credit)
            {
                $PreBalance = $pre_debit - $pre_credit;
                $preDebCre = 'De';
            }else{
                $PreBalance = $pre_credit - $pre_debit;
                $preDebCre = 'Cr';
            }
        }

        // sales
        $sale_info = DB::table("chart_of_account_transaction_details")
            ->leftJoin('voucher_types','chart_of_account_transaction_details.voucher_type_id','=','voucher_types.id')
            ->select(DB::raw('SUM(chart_of_account_transaction_details.debit) as debit'));

        $sale_info->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name)
            ->where('chart_of_account_transaction_details.transaction_type','=','Sales');

        if( ($from_date !== null) && ($to_date !== null) ){
            $sale_info->where('chart_of_account_transaction_details.transaction_date','>=',$from_date)
                ->where('chart_of_account_transaction_details.transaction_date','<=',$to_date);
        }

        if( ($warehouse_id !== '') && ($store_id !== '') ){
            $sale_info->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id)
                ->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        if($warehouse_id !== ''){
            $sale_info->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }

        if($store_id !== ''){
            $sale_info->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        $sale_info->first();

        // sales return info
        $sale_return_info = DB::table("chart_of_account_transaction_details")
            ->leftJoin('voucher_types','chart_of_account_transaction_details.voucher_type_id','=','voucher_types.id')
            ->select(DB::raw('SUM(chart_of_account_transaction_details.debit) as debit'));

        $sale_return_info->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name)
            ->where('chart_of_account_transaction_details.transaction_type','=','Sales Return');

        if( ($from_date !== null) && ($to_date !== null) ){
            $sale_return_info->where('chart_of_account_transaction_details.transaction_date','>=',$from_date)
                ->where('chart_of_account_transaction_details.transaction_date','<=',$to_date);
        }

        if( ($warehouse_id !== '') && ($store_id !== '') ){
            $sale_return_info->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id)
                ->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        if($warehouse_id !== ''){
            $sale_return_info->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }

        if($store_id !== ''){
            $sale_return_info->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        $sale_return_info->first();

        $ledger_data = [
            'sale_info' => [
                'debit' => $sale_info->debit,
                'credit' => 0
            ],
            'sale_return_info' => [
                'debit' => 0,
                'credit' => $sale_return_info->credit ? $sale_return_info->credit : 0
            ],
            'PreBalance' => $PreBalance,
            'preDebCre' => $preDebCre,
            'pre_debit' => $pre_debit,
            'pre_credit' => $pre_credit,
            'chart_of_account_name' => $chart_of_account_name,
            'from_date' => $from_date,
            'to_date' => $to_date,
        ];
        return response()->json(['success'=>true,'response' => $ledger_data], $this->successStatus);
    }

    public function ledgerReport(Request $request){
        $validator = Validator::make($request->all(), [
            'chart_of_account_name'=> 'required',
            'from_date'=> 'required',
            'to_date'=> 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => 'Validation Error.',
                'message' => $validator->errors()
            ];

            return response()->json($response, $this-> validationStatus);
        }

        $store_id = $request->store_id;
        $warehouse_id = $request->warehouse_id;
        $chart_of_account_name = $request->chart_of_account_name;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $gl_pre_valance_data = DB::table('chart_of_account_transaction_details')
            ->select('chart_of_account_transaction_details.chart_of_account_name', DB::raw('SUM(chart_of_account_transaction_details.debit) as debit, SUM(chart_of_account_transaction_details.credit) as credit'));

        $gl_pre_valance_data->where('chart_of_account_transaction_details.transaction_date', '<',$from_date)
            ->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name);

        if( ($warehouse_id !== '') && ($store_id !== '') ){
            $gl_pre_valance_data->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id)
                ->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        if($warehouse_id !== ''){
            $gl_pre_valance_data->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }

        if($store_id !== ''){
            $gl_pre_valance_data->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        $gl_pre_valance_data->groupBy('chart_of_account_transaction_details.chart_of_account_name')->first();

        $PreBalance=0;
        $preDebCre = 'De/Cr';
        $pre_debit = 0;
        $pre_credit = 0;
        if(!empty($gl_pre_valance_data))
        {
            $pre_debit = $gl_pre_valance_data->debit == NULL ? 0 : $gl_pre_valance_data->debit;
            $pre_credit = $gl_pre_valance_data->credit == NULL ? 0 : $gl_pre_valance_data->credit;
            if($pre_debit > $pre_credit)
            {
                $PreBalance = $pre_debit - $pre_credit;
                $preDebCre = 'De';
            }else{
                $PreBalance = $pre_credit - $pre_debit;
                $preDebCre = 'Cr';
            }
        }

        // sales
        $sale_info = DB::table("chart_of_account_transaction_details")
            ->leftJoin('voucher_types','chart_of_account_transaction_details.voucher_type_id','=','voucher_types.id')
            ->select(DB::raw('SUM(chart_of_account_transaction_details.debit) as debit'));

        $sale_info->where('chart_of_account_transaction_details.chart_of_account_name',$chart_of_account_name)
            ->where('chart_of_account_transaction_details.transaction_type','=','Sales');

        if( ($from_date !== null) && ($to_date !== null) ){
            $sale_info->where('chart_of_account_transaction_details.transaction_date','>=',$from_date)
                ->where('chart_of_account_transaction_details.transaction_date','<=',$to_date);
        }

        if( ($warehouse_id !== '') && ($store_id !== '') ){
            $sale_info->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id)
                ->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        if($warehouse_id !== ''){
            $sale_info->where('chart_of_account_transaction_details.warehouse_id',$warehouse_id);
        }

        if($store_id !== ''){
            $sale_info->where('chart_of_account_transaction_details.store_id',$store_id);
        }

        $sale_info->first();

        $ledger_data = [
            'sale_info' => [
                'debit' => $sale_info->debit,
                'credit' => 0
            ],
            'PreBalance' => $PreBalance,
            'preDebCre' => $preDebCre,
            'pre_debit' => $pre_debit,
            'pre_credit' => $pre_credit,
            'chart_of_account_name' => $chart_of_account_name,
            'from_date' => $from_date,
            'to_date' => $to_date,
        ];
        return response()->json(['success'=>true,'response' => $ledger_data], $this->successStatus);
    }

}
