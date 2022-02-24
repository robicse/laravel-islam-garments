<?php

namespace App\Http\Resources;

use App\ProductCategory;
use App\ProductSize;
use App\ProductSubUnit;
use App\ProductUnit;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
//    public function toArray($request)
//    {
//        $data = [
//            'data' => $this->collection->map(function($data) {
//                $category_name = ProductCategory::where('id',$data->product_category_id)->pluck('name')->first();
//                $unit_name = ProductUnit::where('id',$data->product_unit_id)->pluck('name')->first();
//                if(!empty($data->product_sub_unit_id)){
//                    $sub_unit_name = ProductSubUnit::where('id',$data->product_sub_unit_id)->pluck('name')->first();
//                }else{
//                    $sub_unit_name = '';
//                }
//                $size_name = ProductSize::where('id',$data->product_size_id)->pluck('name')->first();
//                return [
//                    'id' => $data->id,
//                    'type' => $data->type,
//                    'product_name' => $data->name,
//                    'category_id' => $data->product_category_id,
//                    'category_name' => $category_name,
//                    'unit_id' => $data->product_unit_id,
//                    'unit_name' => $unit_name,
//                    'sub_unit_name' => $sub_unit_name,
//                    'size_id' => $data->product_size_id,
//                    'size_name' => $size_name,
//                    'product_code' => $data->product_code,
//                    'name' => $data->name,
//                    'barcode' => $data->barcode,
//                    'purchase_price' => $data->purchase_price,
//                    'color' => $data->color,
//                    'design' => $data->design,
//                    'note' => $data->note,
//                    'qty' => 0,
//                    'front_image' => $data->front_image,
//                    'back_image' => $data->back_image
//                ];
//            })
//        ];
//        return [
//            'data' => $data
//        ];
//    }
//
//    public function with($request)
//    {
//        return [
//            'success' => true,
//            'code' => 200,
//        ];
//    }

    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                $category_name = ProductCategory::where('id',$data->product_category_id)->pluck('name')->first();
                $unit_name = ProductUnit::where('id',$data->product_unit_id)->pluck('name')->first();
                $size_name = ProductSize::where('id',$data->product_size_id)->pluck('name')->first();

                if(!empty($data->product_sub_unit_id)){
                    $sub_unit_name = ProductSubUnit::where('id',$data->product_sub_unit_id)->pluck('name')->first();
                }else{
                    $sub_unit_name = '';
                }
                return [
                    'id' => $data->id,
                    'type' => $data->type,
                    'product_name' => $data->name,
                    'category_id' => $data->product_category_id,
                    'category_name' => $category_name,
                    'unit_id' => $data->product_unit_id,
                    'unit_name' => $unit_name,
                    'sub_unit_id' => $data->product_unit_id,
                    'sub_unit_name' => !empty($data->product_unit_id) ? $sub_unit_name : '',
                    'size_id' => $data->product_size_id,
                    'size_name' => $size_name,
                    'product_code' => $data->product_code,
                    'name' => $data->name,
                    'barcode' => $data->barcode,
                    'purchase_price' => $data->purchase_price,
                    'color' => $data->color,
                    'design' => $data->design,
                    'note' => $data->note,
                    'qty' => 0,
                    'front_image' => $data->front_image,
                    'back_image' => $data->back_image
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
