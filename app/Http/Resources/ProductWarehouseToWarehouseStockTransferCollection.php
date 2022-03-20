<?php

namespace App\Http\Resources;

use App\Transaction;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductWarehouseToWarehouseStockTransferCollection extends ResourceCollection
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
                    'transfer_from_warehouse_id' => $data->warehouse_id,
                    'transfer_from_warehouse_name' => warehouseName($data->warehouse_id),
                    'transfer_from_warehouse_phone' => warehouseInfo($data->warehouse_id)->phone,
                    'transfer_from_warehouse_address' => warehouseInfo($data->warehouse_id)->address,
                    'transfer_to_warehouse_id' => $data->transfer_to_warehouse_id,
                    'transfer_to_warehouse_name' => warehouseName($data->transfer_to_warehouse_id),
                    'transfer_to_warehouse_phone' => warehouseInfo($data->transfer_to_warehouse_id)->phone,
                    'transfer_to_warehouse_address' => warehouseInfo($data->transfer_to_warehouse_id)->address,
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
