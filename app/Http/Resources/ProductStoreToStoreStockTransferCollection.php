<?php

namespace App\Http\Resources;

use App\Transaction;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductStoreToStoreStockTransferCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'invoice_no' => $data->invoice_no,
                    'sub_total_amount' => $data->sub_total_amount,
                    'grand_total_amount' => $data->grand_total_amount,
                    'paid_amount' => $data->paid_amount,
                    'due_amount' => $data->due_amount,
                    'date_time' => $data->date_time,
                    'user_name' => userName($data->user_id),
                    'transfer_from_store_id' => $data->store_id,
                    'transfer_from_store_name' => storeName($data->store_id),
                    'transfer_from_store_phone' => storeInfo($data->store_id)->phone,
                    'transfer_from_store_address' => storeInfo($data->store_id)->address,
                    'transfer_to_store_id' => $data->transfer_to_store_id,
                    'transfer_to_store_name' => storeName($data->transfer_to_store_id),
                    'transfer_to_store_phone' => storeInfo($data->transfer_to_store_id)->phone,
                    'transfer_to_store_address' => storeInfo($data->transfer_to_store_id)->address,
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
