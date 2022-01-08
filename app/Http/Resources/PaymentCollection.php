<?php

namespace App\Http\Resources;

use App\Model\Payment;
use App\Model\Vendor;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                $name = '';
                if($data->transaction_type == 'Vehicle Vendor Rent'){
                    $name = Vendor::where('id',$data->paid_user_id)->pluck('name')->first();
                }
                return [
                    'paid_user_id' => $data->paid_user_id,
                    'total_paid' => $data->total_paid,
                    'transaction_type' => $data->transaction_type,
                    'name' => $name,
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
