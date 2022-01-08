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

Route::get('be-a-seller', 'Seller\AuthController@ShowRegForm')->name('seller.registration');
Route::post('be-a-seller/store', 'Seller\AuthController@store')->name('seller.registration.store');

Route::group(['as'=>'seller.','prefix' =>'seller', 'middleware' => ['auth', 'seller']], function(){
    Route::get('dashboard','Seller\DashboardController@index')->name('dashboard');

});
