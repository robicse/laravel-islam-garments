<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/', 'Admin\AuthController@ShowLoginForm')->name('admin.login');
//Route::get('/', 'Frontend\FrontendController@index')->name('index');

//Route::post('/registration','Frontend\FrontendController@register')->name('user.register');
//Route::get('/get-verification-code/{id}', 'Frontend\VerificationController@getVerificationCode')->name('get-verification-code');
//Route::post('/get-verification-code-store', 'Frontend\VerificationController@verification')->name('get-verification-code.store');
//Route::get('/check-verification-code', 'Frontend\VerificationController@CheckVerificationCode')->name('check-verification-code');
//
////Forget Password
//Route::get('/reset-password','Frontend\FrontendController@getPhoneNumber')->name('reset.password');
//Route::post('/otp-store','Frontend\FrontendController@checkPhoneNumber')->name('phone.check');
//Route::post('/change-password','Frontend\FrontendController@otpStore')->name('otp.store');
//Route::post('/new-password/update/{id}','Frontend\FrontendController@passwordUpdate')->name('reset.password.update');

Auth::routes();


Route::group(['middleware' => ['auth', 'user']], function () {
    //this route only for with out resource controller
//    Route::get('/user/dashboard', 'User\DashboardController@index')->name('user.dashboard');
});

