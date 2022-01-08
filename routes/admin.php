<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/admin/login', 'Admin\AuthController@ShowLoginForm')->name('admin.login');
Route::post('/admin/login', 'Admin\AuthController@LoginCheck')->name('admin.login.check');
Route::group(['as'=>'admin.','prefix' =>'admin','namespace'=>'Admin', 'middleware' => ['auth', 'admin']], function(){
    Route::get('dashboard','DashboardController@index')->name('dashboard');
    Route::resource('roles','RoleController');
    Route::post('/roles/permission','RoleController@create_permission');
    Route::resource('staffs','StaffController');
    Route::resource('brands','BrandController');
    Route::resource('suppliers','SupplierController');
    Route::resource('customers','CustomerController');
    Route::resource('products','ProductController');
    Route::resource('categories','CategoryController');
    Route::resource('overall-cost-categories','OverallCostCategoryController');
    Route::resource('overall-cost','OverallCostController');
    Route::resource('access-logs','AccessLogController');

    // vehicle rent from vendor
    Route::get('supplier/due','OrderController@supplier_due')->name('supplier-due');
    Route::get('vehicle/vendor/rent/create','OrderController@vehicle_vendor_rent_create')->name('vehicle-vendor-rent-create');

    // vehicle rent to customer
    Route::get('customer/due','OrderController@customer_due')->name('customer-due');
    Route::get('vehicle/customer/rent/create','OrderController@vehicle_customer_rent_create')->name('vehicle-customer-rent-create');

    // report
    Route::get('report/payments','ReportController@reportPayment')->name('get-report-payment');
    Route::post('report/payments','ReportController@reportPayment')->name('report-payment');
    Route::get('report/payments-print/{date_from}/{date_to}','ReportController@report_payment_print');
    // vendor balance sheet
//    Route::get('report/vendor-balance-sheet','ReportController@vendor_balance_sheet_form')->name('get-report-vendor-balance-sheet');
//    Route::post('report/vendor-balance-sheet','ReportController@vendor_balance_sheet_form')->name('report-vendor-balance-sheet');
//    Route::get('report/vendor-balance-sheet-print/{date_from}/{date_to}','ReportController@report_vendor_balance_sheet_print');
    // customer balance sheet
    Route::get('report/customer-balance-sheet','ReportController@customer_balance_sheet_form')->name('get-report-customer-balance-sheet');
    Route::post('report/customer-balance-sheet','ReportController@customer_balance_sheet_form')->name('report-customer-balance-sheet');
    Route::get('report/customer-balance-sheet-print/{date_from}/{date_to}','ReportController@report_customer_balance_sheet_print');

    // staff balance sheet
    Route::get('report/staff-balance-sheet','ReportController@staff_balance_sheet_form')->name('get-report-staff-balance-sheet');
    Route::post('report/staff-balance-sheet','ReportController@staff_balance_sheet_form')->name('report-staff-balance-sheet');
    Route::get('report/staff-balance-sheet-print/{date_from}/{date_to}','ReportController@report_staff_balance_sheet_print');

    Route::get('report/loss-profit','ReportController@loss_profit')->name('get-report-loss-profit');
    Route::post('report/loss-profit','ReportController@loss_profit')->name('report-loss-profit');
    Route::get('report/loss-profit-print/{date_from}/{date_to}','ReportController@loss_profit_print');
    Route::get('report/loss-profit/export/', 'ReportController@loss_profit_export')->name('report-loss-profit-export');
    Route::get('report/loss-profit-filter-export/{start_date}/{end_date}','ReportController@loss_profit_export_filter')->name('report-loss-profit-filter-export');





    Route::post('categories/is_home', 'CategoryController@updateIsHome')->name('categories.is_home');

    // Admin User Management
    Route::resource('customers','CustomerController');
    Route::get('customers/show/profile/{id}','CustomerController@profileShow')->name('customers.profile.show');
    Route::put('customers/update/profile/{id}','CustomerController@updateProfile')->name('customer.profile.update');
    Route::put('customers/password/update/{id}','CustomerController@updatePassword')->name('customer.password.update');
    Route::get('customers/ban/{id}','CustomerController@banCustomer')->name('customers.ban');

    Route::resource('profile','ProfileController');
    Route::put('password/update/{id}','ProfileController@updatePassword')->name('password.update');

    //performance
    Route::get('/config-cache', 'SystemOptimize@ConfigCache')->name('config.cache');
    Route::get('/clear-cache', 'SystemOptimize@CacheClear')->name('cache.clear');
    Route::get('/view-cache', 'SystemOptimize@ViewCache')->name('view.cache');
    Route::get('/view-clear', 'SystemOptimize@ViewClear')->name('view.clear');
    Route::get('/route-cache', 'SystemOptimize@RouteCache')->name('route.cache');
    Route::get('/route-clear', 'SystemOptimize@RouteClear')->name('route.clear');
    Route::get('/site-optimize', 'SystemOptimize@Settings')->name('site.optimize');

});
