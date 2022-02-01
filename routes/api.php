<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('cache:clear');
    return 'cache clear';
});
Route::get('/route-clear', function() {
    $exitCode = Artisan::call('route:clear');
    return 'route clear';
});
Route::get('/route-cache', function() {
    $exitCode = Artisan::call('route:cache');
    return 'route cache';
});
Route::get('/config-cache', function() {
    $exitCode = Artisan::call('config:cache');
    return 'config:cache';
});
Route::get('/view-cache', function() {
    $exitCode = Artisan::call('view:cache');
    return 'view:cache';
});
Route::get('/view-clear', function() {
    $exitCode = Artisan::call('view:clear');
    return 'view:clear';
});

// only production user er jonno 1 bar e registration hobe
Route::post('register', 'API\FrontendController@register');
Route::post('login', 'API\FrontendController@login');

// warehouse
Route::middleware('auth:api')->get('/warehouse_list', 'API\WarehouseController@warehouseList');
Route::middleware('auth:api')->post('/warehouse_create', 'API\WarehouseController@warehouseCreate');
Route::middleware('auth:api')->post('/warehouse_edit', 'API\WarehouseController@warehouseEdit');
Route::middleware('auth:api')->post('/warehouse_delete', 'API\WarehouseController@warehouseDelete');


// store
Route::middleware('auth:api')->get('/store_list', 'API\StoreController@storeList');
Route::middleware('auth:api')->post('/store_create', 'API\StoreController@storeCreate');
Route::middleware('auth:api')->post('/store_edit', 'API\StoreController@storeEdit');
Route::middleware('auth:api')->post('/store_delete', 'API\StoreController@storeDelete');



// first permission
Route::middleware('auth:api')->get('/permission_list_show', 'API\PermissionController@permissionListShow');
Route::middleware('auth:api')->post('/permission_list_create', 'API\PermissionController@permissionListCreate');
Route::middleware('auth:api')->post('/permission_list_details', 'API\PermissionController@permissionListDetails');
Route::middleware('auth:api')->post('/permission_list_update', 'API\PermissionController@permissionListUpdate');

// second role
Route::middleware('auth:api')->get('/roles', 'API\RoleController@roleList');
Route::middleware('auth:api')->post('/role_permission_create', 'API\RoleController@rolePermissionCreate');
Route::middleware('auth:api')->post('/role_permission_update', 'API\RoleController@rolePermissionUpdate');

// third user
Route::middleware('auth:api')->post('/user_create', 'API\UserController@userCreate');
Route::middleware('auth:api')->get('/user_list', 'API\UserController@userList');
Route::middleware('auth:api')->post('/user_details', 'API\UserController@userDetails');
Route::middleware('auth:api')->post('/user_edit', 'API\UserController@userEdit');
Route::middleware('auth:api')->post('/user_delete', 'API\UserController@userDelete');
Route::middleware('auth:api')->post('/changed_password', 'API\UserController@changedPassword');
Route::middleware('auth:api')->post('/password_reset', 'API\UserController@passwordEeset');

//supplier
Route::middleware('auth:api')->get('/supplier_list', 'API\SupplierController@supplierList');
Route::middleware('auth:api')->post('/supplier_create', 'API\SupplierController@supplierCreate');
Route::middleware('auth:api')->post('/supplier_details', 'API\SupplierController@supplierDetails');
Route::middleware('auth:api')->post('/supplier_update', 'API\SupplierController@supplierUpdate');
Route::middleware('auth:api')->post('/supplier_delete', 'API\SupplierController@supplierDelete');

// pos/whole customer
Route::middleware('auth:api')->get('/customer_list', 'API\CustomerController@customerList');
Route::middleware('auth:api')->post('/pos_customer_create', 'API\CustomerController@posCustomerCreate');
Route::middleware('auth:api')->post('/whole_customer_create', 'API\CustomerController@wholeCustomerCreate');
Route::middleware('auth:api')->post('/customer_details', 'API\CustomerController@customerDetails');
Route::middleware('auth:api')->post('/customer_update', 'API\CustomerController@customerUpdate');
Route::middleware('auth:api')->post('/customer_delete', 'API\CustomerController@customerDelete');
Route::middleware('auth:api')->post('/pos_sale_customer_list_pagination_with_search', 'API\CustomerController@posSaleCustomerListPaginationWithSearch');
Route::middleware('auth:api')->post('/whole_sale_customer_list_pagination_with_search', 'API\CustomerController@wholeSaleCustomerListPaginationWithSearch');

// product unit
Route::middleware('auth:api')->get('/product_unit_list', 'API\ProductUnitController@productUnitList');
Route::middleware('auth:api')->post('/product_unit_create', 'API\ProductUnitController@productUnitCreate');
Route::middleware('auth:api')->post('/product_unit_edit', 'API\ProductUnitController@productUnitEdit');
Route::middleware('auth:api')->post('/product_unit_delete', 'API\ProductUnitController@productUnitDelete');

// product Size
Route::middleware('auth:api')->get('/product_size_list', 'API\ProductSizeController@productSizeList');
Route::middleware('auth:api')->post('/product_size_create', 'API\ProductSizeController@productSizeCreate');
Route::middleware('auth:api')->post('/product_size_edit', 'API\ProductSizeController@productSizeEdit');
Route::middleware('auth:api')->post('/product_size_delete', 'API\ProductSizeController@productSizeDelete');


// expense category
//Route::middleware('auth:api')->get('/expense_category_list', 'API\AccountController@expenseCategoryList');
//Route::middleware('auth:api')->post('/expense_category_create', 'API\AccountController@expenseCategoryCreate');
//Route::middleware('auth:api')->post('/expense_category_edit', 'API\AccountController@expenseCategoryEdit');
//Route::middleware('auth:api')->post('/expense_category_delete', 'API\AccountController@expenseCategoryDelete');

// shop expense
//Route::middleware('auth:api')->get('/store_expense_list', 'API\AccountController@storeExpenseList');
//Route::middleware('auth:api')->post('/store_expense_create', 'API\AccountController@storeExpenseCreate');
//Route::middleware('auth:api')->post('/store_expense_edit', 'API\AccountController@storeExpenseEdit');
//Route::middleware('auth:api')->post('/store_expense_delete', 'API\AccountController@storeExpenseDelete');

// voucher type
//Route::middleware('auth:api')->get('/voucher_type_list', 'API\AccountController@voucherTypeList');
//Route::middleware('auth:api')->post('/voucher_type_create', 'API\AccountController@voucherTypeCreate');
//Route::middleware('auth:api')->post('/voucher_type_edit', 'API\AccountController@voucherTypeEdit');
//Route::middleware('auth:api')->post('/voucher_type_delete', 'API\AccountController@voucherTypeDelete');

// chart of account
//Route::middleware('auth:api')->get('/chart_of_account_list', 'API\AccountController@chartOfAccountList');
//Route::middleware('auth:api')->post('/chart_of_account_list_by_head_name', 'API\AccountController@chartOfAccountListByName');
//Route::middleware('auth:api')->get('/chart_of_account_active_list', 'API\AccountController@chartOfAccountActiveList');
//Route::middleware('auth:api')->get('/chart_of_account_is_transaction_list', 'API\AccountController@chartOfAccountIsTransactionList');
//Route::middleware('auth:api')->get('/chart_of_account_is_cash_book_list', 'API\AccountController@chartOfAccountIsCashBookList');
//Route::middleware('auth:api')->get('/chart_of_account_is_general_ledger_list', 'API\AccountController@chartOfAccountIsGeneralLedgerList');
//Route::middleware('auth:api')->post('/chart_of_account_details', 'API\AccountController@chartOfAccountDetails');
//Route::middleware('auth:api')->post('/chart_of_account_generate_head_code', 'API\AccountController@chartOfAccountGenerateHeadCode');
//Route::middleware('auth:api')->post('/chart_of_account_parent_head_details', 'API\AccountController@chartOfAccountParentHeadDetails');
//Route::middleware('auth:api')->post('/chart_of_account_create', 'API\AccountController@chartOfAccountCreate');
//Route::middleware('auth:api')->post('/chart_of_account_edit', 'API\AccountController@chartOfAccountEdit');
//Route::middleware('auth:api')->post('/chart_of_account_delete', 'API\AccountController@chartOfAccountDelete');

// chart of account transaction
//Route::middleware('auth:api')->get('/chart_of_account_transaction_list', 'API\AccountController@chartOfAccountTransactionList');
//Route::middleware('auth:api')->post('/chart_of_account_transaction_details', 'API\AccountController@chartOfAccountTransactionDetails');
//Route::middleware('auth:api')->post('/chart_of_account_transaction_create', 'API\AccountController@chartOfAccountTransactionCreate');
//Route::middleware('auth:api')->post('/chart_of_account_transaction_edit', 'API\AccountController@chartOfAccountTransactionEdit');
//Route::middleware('auth:api')->post('/chart_of_account_transaction_delete', 'API\AccountController@chartOfAccountTransactionDelete');

// ledger
//Route::middleware('auth:api')->post('/ledger', 'API\AccountController@ledger');
//Route::middleware('auth:api')->post('/cash_book_report', 'API\AccountController@cashBookReport');
//Route::middleware('auth:api')->post('/ledger_report', 'API\AccountController@ledgerReport');
//Route::middleware('auth:api')->post('/balance_sheet', 'API\AccountController@balanceSheet');
