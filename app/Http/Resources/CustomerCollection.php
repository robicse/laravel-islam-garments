<?php

namespace App\Http\Resources;

use App\Party;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'customer_type' => $data->customer_type,
                    'name' => $data->name,
                    'shop_name' => $data->shop_name,
                    'code' => $data->code,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'address' => $data->address,
                    'initial_due' => $data->initial_due,
                    'nid_front' => $data->nid_front,
                    'nid_back' => $data->nid_back,
                    'image' => $data->image,
                    'bank_detail_image' => $data->bank_detail_image,
                    'note' => $data->note,
                    'status' => $data->status,
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
