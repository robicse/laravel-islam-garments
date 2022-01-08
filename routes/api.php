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

Route::group(['middleware' => ['auth:api']], function () {
//    Route::post('/user/profile/update', 'Api\CustomerController@profileUpdate');
//    Route::post('/user/password/update', 'Api\CustomerController@passwordUpdate');
//    Route::get('/user/address', 'Api\AddressController@index');
//    Route::post('/user/address/add', 'Api\AddressController@store');
//    Route::post('/user/address/set-default/{id}', 'Api\AddressController@setDefault');
//    Route::delete('/user/address/delete/{id}', 'Api\AddressController@destroy');
//    Route::post('/user/address/update', 'Api\CustomerController@addressUpdate');
});

//Customer Api
//Route::post('/user/profile/update', 'Api\CustomerController@profileUpdate')->middleware('auth:api');


