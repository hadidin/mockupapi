<?php

use Illuminate\Http\Request;

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

#cloud request
#trigger by kp_cloud
//Route::post('change_lock_status', 'API\KpCloudController@change_lock_status');
//
//

//
//Route::group(['prefix' => 'testapi/{id}'], function ($id) {
//    dd('sassa',$id);
//    Route::post('generate_response', 'HomeController@generate_response');
////    Route::post('snb_void_payment', 'API\VendorHubController@snb_void_payment');
//  });

Route::post('posttestapi/{id}', function ($id) {
    // Only executed if {id} is numeric...
    $response = \App\Http\Controllers\HomeController::generate_response($id);
    return $response;
});

Route::get('gettestapi/{id}', function ($id) {
    // Only executed if {id} is numeric...
    $response = \App\Http\Controllers\HomeController::generate_response($id);
    return $response;
});

////for parking rates
//Route::group(['prefix' => 'parking/rate'], function () {
//    Route::post('/', 'ParkingRateController@storeRate');
//    Route::get('{site_id}/{service}', 'ParkingRateController@rateBySite');
//    Route::post('event', 'ParkingRateController@rateByEvent');
//    Route::post('test', 'ParkingRateController@getParkingFee');
//});

//Route::get('user/{id}', function ($id) {
//    return 'User '.$id;
//});

//Route::post('testapi/{id}', function ($id) {
//    Route::post('generate_response', 'HomeController@generate_response');
//});