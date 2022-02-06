<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentTypeController extends Controller
{
    // supplier
    public function paymentTypeActiveList(){
        try {
            $payment_types = DB::table('payment_types')
                ->select('id','name','status')
                ->where('status',1)
                ->orderBy('id','desc')
                ->get();

            if($payment_types === null){
                $response = APIHelpers::createAPIResponse(true,404,'No Payment Types Found.',null);
                return response()->json($response,404);
            }

            $response = APIHelpers::createAPIResponse(false,200,'',$payment_types);
            return response()->json($response,200);

        } catch (\Exception $e) {
            //return $e->getMessage();
            $response = APIHelpers::createAPIResponse(false,500,'Internal Server Error.',null);
            return response()->json($response,500);
        }
    }
}
