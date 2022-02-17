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
Route::middleware('auth:api')->get('/warehouse_active_list', 'API\WarehouseController@warehouseActiveList');


// store
Route::middleware('auth:api')->get('/store_list', 'API\StoreController@storeList');
Route::middleware('auth:api')->post('/store_create', 'API\StoreController@storeCreate');
Route::middleware('auth:api')->post('/store_edit', 'API\StoreController@storeEdit');
Route::middleware('auth:api')->post('/store_delete', 'API\StoreController@storeDelete');
Route::middleware('auth:api')->get('/store_active_list', 'API\StoreController@storeActiveList');



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
Route::middleware('auth:api')->post('/supplier_image', 'API\SupplierController@supplierImage');
Route::middleware('auth:api')->get('/supplier_active_list', 'API\SupplierController@supplierActiveList');

// pos/whole customer
Route::middleware('auth:api')->get('/customer_list', 'API\CustomerController@customerList');
Route::middleware('auth:api')->post('/pos_customer_create', 'API\CustomerController@posCustomerCreate');
Route::middleware('auth:api')->post('/whole_customer_create', 'API\CustomerController@wholeCustomerCreate');
Route::middleware('auth:api')->post('/customer_details', 'API\CustomerController@customerDetails');
Route::middleware('auth:api')->post('/customer_update', 'API\CustomerController@customerUpdate');
Route::middleware('auth:api')->post('/customer_delete', 'API\CustomerController@customerDelete');
Route::middleware('auth:api')->post('/pos_sale_customer_list_pagination_with_search', 'API\CustomerController@posSaleCustomerListPaginationWithSearch');
Route::middleware('auth:api')->post('/whole_sale_customer_list_pagination_with_search', 'API\CustomerController@wholeSaleCustomerListPaginationWithSearch');

// product brand
Route::middleware('auth:api')->get('/product_brand_list', 'API\ProductBrandController@productBrandList');
Route::middleware('auth:api')->post('/product_brand_create', 'API\ProductBrandController@productBrandCreate');
Route::middleware('auth:api')->post('/product_brand_edit', 'API\ProductBrandController@productBrandEdit');
Route::middleware('auth:api')->post('/product_brand_delete', 'API\ProductBrandController@productBrandDelete');

// product category
Route::middleware('auth:api')->get('/product_category_list', 'API\ProductCategoryController@productCategoryList');
Route::middleware('auth:api')->post('/product_category_create', 'API\ProductCategoryController@productCategoryCreate');
Route::middleware('auth:api')->post('/product_category_edit', 'API\ProductCategoryController@productCategoryEdit');
Route::middleware('auth:api')->post('/product_category_delete', 'API\ProductCategoryController@productCategoryDelete');
Route::middleware('auth:api')->get('/product_category_active_list', 'API\ProductCategoryController@productCategoryActiveList');

// product unit
Route::middleware('auth:api')->get('/product_unit_list', 'API\ProductUnitController@productUnitList');
Route::middleware('auth:api')->post('/product_unit_create', 'API\ProductUnitController@productUnitCreate');
Route::middleware('auth:api')->post('/product_unit_edit', 'API\ProductUnitController@productUnitEdit');
Route::middleware('auth:api')->post('/product_unit_delete', 'API\ProductUnitController@productUnitDelete');
Route::middleware('auth:api')->get('/product_unit_active_list', 'API\ProductUnitController@productUnitActiveList');

// product sub unit
Route::middleware('auth:api')->get('/product_sub_unit_list', 'API\ProductSubUnitController@productSubUnitList');
Route::middleware('auth:api')->post('/product_sub_unit_create', 'API\ProductSubUnitController@productSubUnitCreate');
Route::middleware('auth:api')->post('/product_sub_unit_edit', 'API\ProductSubUnitController@productSubUnitEdit');
Route::middleware('auth:api')->post('/product_sub_unit_delete', 'API\ProductSubUnitController@productSubUnitDelete');
Route::middleware('auth:api')->get('/product_sub_unit_active_list', 'API\ProductSubUnitController@productSubUnitActiveList');

// product Size
Route::middleware('auth:api')->get('/product_size_list', 'API\ProductSizeController@productSizeList');
Route::middleware('auth:api')->post('/product_size_create', 'API\ProductSizeController@productSizeCreate');
Route::middleware('auth:api')->post('/product_size_edit', 'API\ProductSizeController@productSizeEdit');
Route::middleware('auth:api')->post('/product_size_delete', 'API\ProductSizeController@productSizeDelete');
Route::middleware('auth:api')->get('/product_size_active_list', 'API\ProductSizeController@productSizeActiveList');

// product
Route::middleware('auth:api')->post('/check_exists_product', 'API\ProductController@checkExistsProduct');
Route::middleware('auth:api')->post('/product_create', 'API\ProductController@productCreate');
Route::middleware('auth:api')->post('/product_edit', 'API\ProductController@productEdit');
Route::middleware('auth:api')->post('/product_delete', 'API\ProductController@productDelete');
Route::middleware('auth:api')->post('/product_image', 'API\ProductController@productImage');
Route::middleware('auth:api')->post('/product_list_with_search', 'API\ProductController@productListWithSearch');
Route::middleware('auth:api')->post('/product_info_for_stock_in', 'API\ProductController@productInfoForStockIn');
Route::middleware('auth:api')->get('/product_active_list', 'API\ProductController@productActiveList');

// payment type
Route::middleware('auth:api')->get('/payment_type_active_list', 'API\PaymentTypeController@paymentTypeActiveList');

// warehouse stock in
//Route::middleware('auth:api')->get('/product_purchase_list', 'API\ProductPurchaseController@productPurchaseList');
Route::middleware('auth:api')->post('/product_purchase_details', 'API\ProductPurchaseController@productPurchaseDetails');
Route::middleware('auth:api')->post('/product_purchase_details_print', 'API\ProductPurchaseController@productPurchaseDetailsPrint');
Route::middleware('auth:api')->post('/product_purchase_list_pagination_with_search', 'API\ProductPurchaseController@productPurchaseListPaginationWithSearch');
Route::middleware('auth:api')->post('/product_purchase_create', 'API\ProductPurchaseController@productPurchaseCreate');
//Route::middleware('auth:api')->post('/product_pos_purchase_edit', 'API\ProductPurchaseController@productPOSPurchaseEdit');
//Route::middleware('auth:api')->post('/product_pos_purchase_delete', 'API\ProductPurchaseController@productPOSPurchaseDelete');

// warehouse stock list
Route::middleware('auth:api')->post('/stock_transaction_list_with_search', 'API\StockController@stockTransactionListWithSearch');
Route::middleware('auth:api')->post('/warehouse_current_stock_by_id', 'API\StockController@warehouseCurrentStockById');

// stock transfer
Route::middleware('auth:api')->post('/warehouse_current_stock_list_pagination_product_name', 'API\PaginationController@warehouseCurrentStockListPaginationProductName');
Route::middleware('auth:api')->post('/warehouse_to_store_stock_create', 'API\StockTransferController@warehouseToStoreStockCreate');
Route::middleware('auth:api')->post('/stock_transfer_list_with_search', 'API\StockTransferController@stockTransferListWithSearch');
Route::middleware('auth:api')->post('/stock_transfer_details', 'API\StockTransferController@stockTransferDetails');
Route::middleware('auth:api')->post('/stock_transfer_details_print', 'API\StockTransferController@stockTransferDetailsPrint');

// store stock list
Route::middleware('auth:api')->post('/store_current_stock_by_id', 'API\StockController@storeCurrentStockById');


// expense category
Route::middleware('auth:api')->get('/expense_category_list', 'API\ExpenseCategoryController@expenseCategoryList');
Route::middleware('auth:api')->get('/expense_category_active_list', 'API\ExpenseCategoryController@expenseCategoryActiveList');
Route::middleware('auth:api')->post('/expense_category_create', 'API\ExpenseCategoryController@expenseCategoryCreate');
Route::middleware('auth:api')->post('/expense_category_edit', 'API\ExpenseCategoryController@expenseCategoryEdit');
Route::middleware('auth:api')->post('/expense_category_delete', 'API\ExpenseCategoryController@expenseCategoryDelete');

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
Route::middleware('auth:api')->get('/chart_of_account_active_list', 'API\AccountController@chartOfAccountActiveList');
Route::middleware('auth:api')->get('/chart_of_account_is_transaction_list', 'API\AccountController@chartOfAccountIsTransactionList');
//Route::middleware('auth:api')->get('/chart_of_account_is_cash_book_list', 'API\AccountController@chartOfAccountIsCashBookList');
Route::middleware('auth:api')->get('/chart_of_account_is_general_ledger_list', 'API\AccountController@chartOfAccountIsGeneralLedgerList');
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
Route::middleware('auth:api')->post('/ledger', 'API\AccountController@ledger');
Route::middleware('auth:api')->post('/cash_book_report', 'API\AccountController@cashBookReport');
Route::middleware('auth:api')->post('/ledger_report', 'API\AccountController@ledgerReport');
//Route::middleware('auth:api')->post('/balance_sheet', 'API\AccountController@balanceSheet');

// dashboard history
Route::middleware('auth:api')->get('/dashboard_count_information', 'API\DashboardController@dashboardInformation');
