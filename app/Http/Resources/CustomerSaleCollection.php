<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerSaleCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'invoice_no' => $data->invoice_no,
                    'date_time' => $data->date_time,
                    'grand_total_amount' => $data->grand_total_amount,
                    'user_name' => userName($data->user_id),
                    'customer_id' => $data->customer_id,
                    'customer_name' => customerName($data->customer_id),
                    'store_id' => $data->store_id,
                    'store_name' => storeName($data->store_id),
                    'payment_type' => paymentType($data->payment_type_id),
                ];
            })
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
