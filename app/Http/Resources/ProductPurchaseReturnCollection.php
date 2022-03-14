<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductPurchaseReturnCollection extends ResourceCollection
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
                    'warehouse_name' => warehouseName($data->warehouse_id),
                    'warehouse_phone' => warehouseInfo($data->warehouse_id)->phone,
                    'warehouse_address' => warehouseInfo($data->warehouse_id)->address,
                    'user_name' => userName($data->user_id),
                    'supplier_id' => $data->supplier_id,
                    'supplier_name' => supplierInfo($data->supplier_id)->name,
                    'supplier_phone' => supplierInfo($data->supplier_id)->phone,
                    'supplier_address' => supplierInfo($data->supplier_id)->address,
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
