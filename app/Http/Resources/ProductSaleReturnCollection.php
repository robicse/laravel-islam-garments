<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductSaleReturnCollection extends ResourceCollection
{
//    public function toArray($request)
//    {
//        return [
//            'data' => $this->collection->map(function($data) {
//                return [
//                    'id' => $data->id,
//                ];
//            })
//        ];
//    }




    public function toArray($request)
    {
        $data = [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'invoice_no' => $data->invoice_no,
                    'sub_total_amount' => $data->sub_total_amount,
                    'miscellaneous_comment' => $data->miscellaneous_comment,
                    'miscellaneous_charge' => $data->miscellaneous_charge,
                    'discount_type' => $data->discount_type,
                    'discount_percent' => $data->discount_percent,
                    'discount_amount' => $data->discount_amount,
                    'total_vat_amount' => $data->total_vat_amount,
                    'after_discount_amount' => $data->after_discount_amount,
                    'grand_total_amount' => $data->grand_total_amount,
                    'paid_amount' => $data->paid_amount,
                    'due_amount' => $data->due_amount,
                    'date_time' => $data->date_time,
                    'store_name' => storeName($data->store_id),
                    'store_phone' => storeInfo($data->store_id)->phone,
                    'store_address' => storeInfo($data->store_id)->address,
                    'user_name' => userName($data->user_id),
                    'customer_id' => $data->customer_id,
                    'customer_name' => customerInfo($data->customer_id)->name,
                    'customer_phone' => customerInfo($data->customer_id)->phone,
                    'customer_address' => customerInfo($data->customer_id)->address,
                ];
            })
        ];
        return [
            'data' => $data
        ];
    }




    public function with($request)
    {
        return [
            'success' => true,
            'code' => 200
        ];
    }
}
