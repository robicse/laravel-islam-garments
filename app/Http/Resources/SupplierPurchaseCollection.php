<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierPurchaseCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'invoice_no' => $data->invoice_no,
                    'supplier_invoice_no' => $data->supplier_invoice_no,
                    'grand_total_amount' => $data->grand_total_amount,
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
