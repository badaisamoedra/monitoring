<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->group(['prefix' => 'auth'], function($api) {
        $api->post('login', 'App\\Api\\V1\\Controllers\\AuthenticateController@login');
    });

    $api->group(['middleware' => 'auth:api'], function($api) {
        $api->group(['namespace' => 'App\\Api\\V1\\Controllers\\'], function($api) {
            $api->get('tes', 'AuthenticateController@getAuthUser');
        });
    });
});