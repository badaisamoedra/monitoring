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
    $api->group(['prefix' => 'auth', 'middleware' => 'cors'], function($api) {
        $api->post('login', 'App\\Api\\V1\\Controllers\\AuthenticateController@login');
        $api->get('refresh', 'App\\Api\\V1\\Controllers\\AuthenticateController@refreshToken');
    });

    $api->group(['middleware' => ['cors','auth:api']], function($api) {
        $api->group(['namespace' => 'App\\Api\\V1\\Controllers\\'], function($api) {
            $api->get('tes', 'AuthenticateController@getAuthUser');

            //user profile
            $api->get('user-profile', 'UserProfileController@index');
            $api->get('user-profile/{id}', 'UserProfileController@show');
            $api->post('register', 'UserProfileController@store');
            $api->put('user-profile/{id}', 'UserProfileController@update');
            $api->delete('user-profile/{id}', 'UserProfileController@destroy');
            //role
            $api->get('role', 'RoleController@index');
            $api->get('role/{id}', 'RoleController@show');
            $api->post('role', 'RoleController@store');
            $api->put('role/{id}', 'RoleController@update');
            $api->delete('role/{id}', 'RoleController@destroy');
            //alert
            $api->get('alert', 'AlertController@index');
            $api->get('alert/{id}', 'AlertController@show');
            $api->post('alert', 'AlertController@store');
            $api->put('alert/{id}', 'AlertController@update');
            $api->delete('alert/{id}', 'AlertController@destroy');
            //areas
            $api->get('areas', 'AreasController@index');
            $api->get('areas/{id}', 'AreasController@show');
            $api->post('areas', 'AreasController@store');
            $api->put('areas/{id}', 'AreasController@update');
            $api->delete('areas/{id}', 'AreasController@destroy');
            //zone
            $api->get('zone', 'ZoneController@index');
            $api->get('zone/{id}', 'ZoneController@show');
            $api->post('zone', 'ZoneController@store');
            $api->put('zone/{id}', 'ZoneController@update');
            $api->delete('zone/{id}', 'ZoneController@destroy');
            //zone detail coordinate
            $api->get('zone-detail-coordinate', 'ZoneDetailCoordinateController@index');
            $api->get('zone-detail-coordinate/{id}', 'ZoneDetailCoordinateController@show');
            $api->post('zone-detail-coordinate', 'ZoneDetailCoordinateController@store');
            $api->put('zone-detail-coordinate/{id}', 'ZoneDetailCoordinateController@update');
            $api->delete('zone-detail-coordinate/{id}', 'ZoneDetailCoordinateController@destroy');
            //role pair area
            $api->get('role-pair-area', 'RolePairAreaController@index');
            $api->get('role-pair-area/{id}', 'RolePairAreaController@show');
            $api->post('role-pair-area', 'RolePairAreaController@store');
            $api->put('role-pair-area/{id}', 'RolePairAreaController@update');
            $api->delete('role-pair-area/{id}', 'RolePairAreaController@destroy');
            //driver
            $api->get('driver', 'DriverController@index');
            $api->get('driver/{id}', 'DriverController@show');
            $api->post('driver', 'DriverController@store');
            $api->put('driver/{id}', 'DriverController@update');
            $api->delete('driver/{id}', 'DriverController@destroy');
            //notification
            $api->get('notification', 'NotificationController@index');
            $api->get('notification/{id}', 'NotificationController@show');
            $api->post('notification', 'NotificationController@store');
            $api->put('notification/{id}', 'NotificationController@update');
            $api->delete('notification/{id}', 'NotificationController@destroy');
            //transaction vehicle pair
            $api->get('transaction-vehicle-pair', 'TransactionVehiclePairController@index');
            $api->get('transaction-vehicle-pair/{id}', 'TransactionVehiclePairController@show');
            $api->post('transaction-vehicle-pair', 'TransactionVehiclePairController@store');
            $api->put('transaction-vehicle-pair/{id}', 'TransactionVehiclePairController@update');
            $api->delete('transaction-vehicle-pair/{id}', 'TransactionVehiclePairController@destroy');
            //vehicle brand
            $api->get('vehicle-brand', 'VehicleBrandController@index');
            $api->get('vehicle-brand/{id}', 'VehicleBrandController@show');
            $api->post('vehicle-brand', 'VehicleBrandController@store');
            $api->put('vehicle-brand/{id}', 'VehicleBrandController@update');
            $api->delete('vehicle-brand/{id}', 'VehicleBrandController@destroy');
            //vehicle
            $api->get('vehicle', 'VehicleController@index');
            $api->get('vehicle/{id}', 'VehicleController@show');
            $api->post('vehicle', 'VehicleController@store');
            $api->put('vehicle/{id}', 'VehicleController@update');
            $api->delete('vehicle/{id}', 'VehicleController@destroy');
            //vehicle maintenance
            $api->get('vehicle-maintenance', 'VehicleMaintenanceController@index');
            $api->get('vehicle-maintenance/{id}', 'VehicleMaintenanceController@show');
            $api->post('vehicle-maintenance', 'VehicleMaintenanceController@store');
            $api->put('vehicle-maintenance/{id}', 'VehicleMaintenanceController@update');
            $api->delete('vehicle-maintenance/{id}', 'VehicleMaintenanceController@destroy');
            //vehicle model
            $api->get('vehicle-model', 'VehicleModelController@index');
            $api->get('vehicle-model/{id}', 'VehicleModelController@show');
            $api->post('vehicle-model', 'VehicleModelController@store');
            $api->put('vehicle-model/{id}', 'VehicleModelController@update');
            $api->delete('vehicle-model/{id}', 'VehicleModelController@destroy');
            //vehicle status
            $api->get('vehicle-status', 'VehicleStatusController@index');
            $api->get('vehicle-status/{id}', 'VehicleStatusController@show');
            $api->post('vehicle-status', 'VehicleStatusController@store');
            $api->put('vehicle-status/{id}', 'VehicleStatusController@update');
            $api->delete('vehicle-status/{id}', 'VehicleStatusController@destroy');
            //report
            $api->get('rpt-driverScore', 'ReportController@reportDriverScore');
            $api->get('rpt-historycal', 'ReportController@reportHistorical');
            $api->get('rpt-unplugged', 'ReportController@reportUnPlugged');
            $api->get('rpt-outOfGeofence', 'ReportController@reportOutOfGeofence');
            $api->get('rpt-notification', 'ReportController@reportNotification');
            $api->get('rpt-KMdriven', 'ReportController@reportKMDriven');
            $api->get('rpt-fleetUtilisation', 'ReportController@reportFleetUtilisation');
            $api->get('rpt-gpsNotUpdate', 'ReportController@reportGpsNotUpdate');
            $api->get('rpt-overSpeed', 'ReportController@reportOverSpeed');
        });
    });

    $api->group(['middleware' => ['cors']], function($api) {
        $api->group(['namespace' => 'App\\Api\\V1\\Controllers\\'], function($api) {
            //mw mapping
            $api->get('mw-mapping', 'MappingController@index');
            $api->get('mw-mapping/{id}', 'MappingController@show');
            $api->post('mw-mapping', 'MappingController@store');
            $api->put('mw-mapping/{id}', 'MappingController@update');
            $api->delete('mw-mapping/{id}', 'MappingController@destroy');
            $api->get('mw-mapping-total', 'MappingController@getTotalVehicleStatus');
        });
    });

});