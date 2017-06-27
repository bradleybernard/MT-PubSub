<?php

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

Route::post('login', 'SubscriptionsController@loginUser');
Route::post('logout', 'SubscriptionsController@logoutUser');
Route::post('check', 'SubscriptionsController@checkUser');
Route::post('toggle', 'SubscriptionsController@toggleSubscription');

