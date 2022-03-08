<?php

namespace App\Http\Resources;

use App\Transaction;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductPurchaseCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'invoice_no' => $data->invoice_no,
                    'sub_total_amount' => $data->sub_total_amount,
                    'discount_type' => $data->discount_type,
                    'discount_amount' => $data->discount_amount,
                    'discount_percent' => $data->discount_percent,
                    'grand_total_amount' => $data->grand_total_amount,
                    'paid_amount' => $data->paid_amount,
                    'due_amount' => $data->due_amount,
                    'date_time' => $data->date_time,
                    'user_name' => userName($data->user_id),
                    'supplier_id' => $data->supplier_id,
                    'supplier_name' => SupplierName($data->supplier_id),
                    'warehouse_id' => $data->warehouse_id,
                    'warehouse_name' => warehouseName($data->warehouse_id),
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
