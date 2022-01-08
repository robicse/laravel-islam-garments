<?php

namespace App\Http\Controllers\Admin;

use App\Model\Attribute;
use App\Model\Brand;
use App\Model\Category;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\Order;
use App\Model\Product;
use App\Model\Subcategory;
use App\Model\SubSubcategory;
use App\Model\Supplier;
use App\Model\Vehicle;
use App\Model\VehicleDriverAssign;
use App\Model\Vendor;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStaffs = User::where('user_type','staff')->count();
        $totalUsers = User::where('user_type','customer')->count();
        $totalCategories = Category::count();
        $totalSubCategories = Subcategory::count();
        $totalBrands = Brand::count();
        $totalSuppliers = Supplier::count();
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();

        return view('backend.admin.dashboard',
            compact(
                'totalStaffs',
                'totalBrands',
                'totalUsers',
                'totalCategories',
                'totalSubCategories',
                'totalSuppliers',
                'totalCustomers',
                'totalProducts'
            ));
    }
}
