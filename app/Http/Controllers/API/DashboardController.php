<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function dashboardInformation(Request $request){
        $info = [
            'totalStaff' => totalStaff() != null ? totalStaff() : 0,
            'totalSupplier' => totalSupplier() != null ? totalSupplier() : 0,
            'totalCustomer' => totalCustomer() != null ? totalCustomer() : 0,
            'todayPurchase' => todayPurchase() != null ? todayPurchase() : 0,
            'todayCashPurchase' => todayCashPurchase() != null ? todayCashPurchase() : 0,
            'totalPurchase' => totalPurchase() != null ? totalPurchase() : 0,
            'totalCashPurchase' => totalCashPurchase() != null ? totalCashPurchase() : 0,
            'warehouseTotalCurrentStock' => warehouseTotalCurrentStock() != null ? warehouseTotalCurrentStock() : 0,
            'warehouseTotalCurrentStockAmount' => warehouseProductCurrentStockAmount() != null ? warehouseProductCurrentStockAmount() : 0,
            'storeTotalCurrentStock' => storeTotalCurrentStock() != null ? storeTotalCurrentStock() : 0,
            'storeTotalCurrentStockAmount' => storeProductCurrentStockAmount() != null ? storeProductCurrentStockAmount() : 0,
            'todaySale' => todaySale() != null ? todaySale() : 0,
            'todayCashSale' => todayCashSale() != null ? todayCashSale() : 0,
            'totalSale' => totalSale() != null ? totalSale() : 0,
            'totalCashSale' => totalCashSale() != null ? totalCashSale() : 0,
            'warehouseWiseInformation' => warehouseWiseInformation() != null ? warehouseWiseInformation() : 0,
            'storeWiseInformation' => storeWiseInformation() != null ? storeWiseInformation() : 0,
        ];

        return response()->json(['success'=>true,'response' => $info], 200);
    }
}
