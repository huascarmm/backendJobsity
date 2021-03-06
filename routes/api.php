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

Route::group([
    'prefix'=> 'user',
    'namespace'=>'User'
], function(){
    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::get('info/{id}', 'AuthController@user');
    Route::get('userPosts/{id}', 'AuthController@userPosts');
});

Route::resource('entries', 'EntriesController');