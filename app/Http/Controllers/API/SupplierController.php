<?php

namespace App\Http\Controllers\API;

use App\ChartOfAccount;
use App\Employee;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\Supplier;
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
                ->select('id','code','name','phone','email','address','nid','status')
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
                ->select('id','code','name','phone','email','address','nid','status')
                ->orderBy('id','desc')
                ->get();

            if($suppliers === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Suppliers Found.',null);
                return response()->json($response,404);
            }

            $supplier_arr = [];
            foreach ($suppliers as $supplier) {
                $nested_data['id'] = $supplier->id;
                $nested_data['code'] = $supplier->code;
                $nested_data['name'] = $supplier->name;
                $nested_data['phone'] = $supplier->phone;
                $nested_data['email'] = $supplier->email;
                $nested_data['address'] = $supplier->address;
                $nested_data['nid'] = $supplier->nid;
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
            $supplier->code = $final_supplier_code;
            $supplier->phone = $request->phone;
            $supplier->email = $request->email;
            $supplier->address = $request->address;
            //$supplier->initial_due = $request->initial_due;
            $supplier->save();
            $insert_id = $supplier->id;

            if($insert_id){
                $account = DB::table('chart_of_accounts')
                    ->where('head_level',3)
                    ->where('head_code', 'like', '50101%')
                    ->Orderby('created_at', 'desc')
                    ->limit(1)
                    ->first();

                if(!empty($account)){
                    $head_code=$account->head_code+1;
                }else{
                    $head_code="5010100001";
                }
                $head_name = $request->name.'-'.$request->code;

                $parent_head_name = 'Account Payable';
                $head_level = 3;
                $head_type = 'L';

                $coa = new ChartOfAccount();
                $coa->head_code             = $head_code;
                $coa->head_name             = $head_name;
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

            $supplier = DB::table("suppliders")->where('id',$request->supplier_id)->latest()->first();
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
            $supplier->name = $request->name;
            $supplier->phone = $request->phone;
            $supplier->email = $request->email;
            $supplier->address = $request->address;
            $supplier->status = $request->status;
            //$supplier->initial_due = $request->initial_due;
            $supplier->save();
            $update_supplier = $supplier->save();

            if($update_supplier){
                $chart_of_account = ChartOfAccount::where('name_code',$supplier->code)->first();
                $chart_of_account->head_name=$request->name;
                $chart_of_account->save();
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
            $proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
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
}
