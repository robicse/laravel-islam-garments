<?php

namespace App\Http\Resources;

use App\ProductCategory;
use App\ProductSize;
use App\ProductUnit;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                $category_name = ProductCategory::where('id',$data->product_category_id)->pluck('name')->first();
                $unit_name = ProductUnit::where('id',$data->product_unit_id)->pluck('name')->first();
                $size_name = ProductSize::where('id',$data->product_size_id)->pluck('name')->first();
                return [
                    'id' => $data->id,
                    'type' => $data->type,
                    'product_name' => $data->name,
                    'category_id' => $data->product_category_id,
                    'category_name' => $category_name,
                    'unit_id' => $data->product_unit_id,
                    'unit_name' => $unit_name,
                    'size_id' => $data->product_size_id,
                    'size_name' => $size_name,
                    'product_code' => $data->product_code,
                    'barcode' => $data->barcode,
                    'purchase_price' => $data->purchase_price,
                    'qty' => 0,
                    'image' => $data->image
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
