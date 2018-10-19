<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMappingHistory;
use App\Models\RptDriverScoring;
use Carbon\Carbon;
use Auth;

class ReportController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        // $this->globalCrudRepo = $globalCrudRepo;
        // $this->globalCrudRepo->setModel(new MwMappingHistory());
    }

    public function index()
    {
        $data = MwMappingHistory::select([
                                        'device_time',
                                        'license_plate',
                                        'longitude',
                                        'latitude',
                                        'speed',
                                        'total_odometer',
                                        'satellite',
                                        'last_location'
                                       ])->get();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportDriverScore(Request $request){
        // $this->filters($request);
        $data = RptDriverScoring::raw(function($collection) use ($request)
        {  
            $query = [
            [
                '$project' => array(
                    'created_at'     => '$created_at',
                    'driver'         => '$driver_name',
                    'license_plate'  => '$license_plate',
                    'total'          => '$score',
                )
            ]];
            return $collection->aggregate($query);
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportFleetUtilisation(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {  
            $search['$match'] = [];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            $query = [
                 [
                    '$project' => array(
                        '_id'               => 1,
                        'license_plate'     => 1,
                        'vehicle_number'    => 1,
                        'machine_number'    => 1,
                        'speed'             => 1,
                        'duration_out_zone' => 1,
                        'fuel_consumed'     => 1
                    )
                ],
                [
                    '$group' => array(
                        '_id' => [
                            'license_plate'  => '$license_plate',
                            'vehicle_number' => '$vehicle_number',
                            'machine_number' => '$machine_number',
                        ],
                       
                        'total_data'  => ['$sum' => 1],
                        'park_time'   => ['$sum' => '$park_time'],
                        'moving_time' => ['$sum' => '$moving_time'],
                        'idle_time'   => ['$sum' => '$idle_time'],
                        'engine_on_time' => ['$sum' => '$engine_on_time'],
                        'total_mileage'  => ['$sum' => '$total_odometer'],
                        'speed' => ['$sum' => '$speed'],
                        'duration_out_zone' => ['$sum' => '$duration_out_zone'],
                        'fuel' => ['$sum' => 'fuel_consumed'],
                    )
                ]
                ,[
                    '$project' => array(
                        'total_data'  => '$total_data',
                        'park_time'   => '$park_time',
                        'moving_time' => '$moving_time',
                        'idle_time'   => '$idle_time',
                        'fuel'        => '$fuel',
                        'engine_on_time' => '$engine_on_time',
                        'total_mileage'  => '$total_mileage',
                        'average_speed'  => [
                            '$divide' => [ '$speed', '$total_data']
                        ],
                        // 'rasio_engine_on' => [
                        //     '$multiply' => [[
                        //         '$divide' => [100, '$engine_on_time']
                        //     ], 100]
                        // ],
                        'duration_out_zone' => '$duration_out_zone',
                        
                    )
                ],
            ];
            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportGpsNotUpdate(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {
            $search['$match'] = [];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            
            $query = [
                [
                    '$project' => array(
                        'gps_supplier'   => [
                            '$ifNull' => [ null, "PT Blue Chip Transland / Teltonika" ]
                        ],
                        'branch'         => [
                            '$ifNull' => [ null, "Ambilnya darimana nih?" ] //need to confirm
                        ],
                        'license_plate'  => '$license_plate',
                        'imei'           => '$imei',
                        'vin'            => '$vehicle_number',
                        'created_at'     => '$created_at',
                        'address'        => '$last_location',
                        'gps_satellite'  => '$satellite', //need to confirm
                        'gsm_signal'     => '$gsm_signal_level', //need to confirm
                    )
                ],
                [
                    '$sort' => ['created_at' => -1]
                ]

            ];

            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportHistorical(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {
            $search['$match'] = [];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            
            $query = [
                [
                '$project' => array(
                    'date_time'      => '$device_time',
                    'license_plate'  => '$license_plate',
                    'engine_status'  => [
                                        '$cond' => [
                                            'if'   => [ '$eq' => [ '$ignition', 0 ]],
                                            'then' => 'Engine Off',
                                            'else' => 'Engine On'
                                        ]
                    ],
                    'longitude'      => '$longitude',
                    'latitude'       => '$latitude',
                    'speed'          => '$speed',
                    'mileage'        => '$total_odometer',
                    'alert'          => '$alert_status',
                    'out_of_zone'    => [
                                        '$cond' => [
                                            'if'   => [ '$eq' => [ '$is_out_zone', true ]],
                                            'then' => 'Out Zone',
                                            'else' => 'In Zone'
                                        ]
                    ],
                    'heading'        => '$direction',
                    'immo'           => [
                                        '$cond' => [
                                            'if'   => [ '$eq' => [ '$digital_output_1', 1 ]],
                                            'then' => 'On',
                                            'else' => 'Off'
                                        ]
                    ],
                     // sleepmode (deep sleep)
                    'satellite'      => '$satellite',
                    'accu'           => '$internal_battery_voltage',
                    'gsm_signal'     => '$gsm_signal_level',
                    'address'        => '$last_location',
                    'created_at'     => '$created_at'
                )
                ],
                [
                    '$sort' => ['created_at' => -1]
                ]
            
            ];

            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportKMDriven(Request $request){
        if(!$request->has('startDate') && !$request->has('endDate')){
            throw new \Exception("Filter is required.");
        }
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {
            $search['$match'] = [];
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            
            $query = [
                [
                    '$project' => array(
                        'license_plate'  => '$license_plate',
                        'driver'         => '$driver_name',
                        'vin'            => '$vehicle_number',
                        'machine_number' => '$machine_number',
                        'start_address'  => '$last_location',
                        'end_address'    => '$last_location',
                        'km_start'       => '$total_odometer',
                        'km_end'         => '$total_odometer',
                        'km_driven'      => '$total_odometer',
                        'total_odometer' => '$total_odometer',
                    )
                ],
                [
                    '$sort' => ['created_at' => -1]
                ]

            ];

            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportNotification(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {
            $search['$match'] = [];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            
            $query = [
                [
                    '$project' => array(
                        'created_at'     => '$created_at',
                        'license_plate'  => '$license_plate',
                        'engine_status'  => [
                            '$cond' => [
                                'if'   => [ '$eq' => [ '$ignition', 0 ]],
                                'then' => 'Engine Off',
                                'else' => 'Engine On'
                            ]
                        ],
                        'heading'        => '$direction',
                        'longitude'      => '$longitude',
                        'latitude'       => '$latitude',
                        'speed'          => '$speed',
                        'mileage'        => '$total_odometer',
                        'alert'          => '$alert_status',
                        'address'        => '$last_location',
                    )
                ],
                [
                    '$sort' => ['created_at' => -1]
                ]

            ];

            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportOutOfGeofence(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {
            $search['$match']['is_out_zone'] = true;
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            
            $query = [
                [
                    '$project' => array(
                        'license_plate'  => '$license_plate',
                        'created_at'     => '$created_at',
                        'vin'            => '$vehicle_number',
                        'machine_number' => '$machine_number',
                        'duration'       => '$duration_out_zone', //ini di sum ga?
                        'speed'          => '$speed',
                        'address'        => '$last_location',
                        'is_out_zone'    => '$is_out_zone',
                        'geofence_area'   => 'Ambil dari mana nih?' //ini darimana?
                    )
                ],
                [
                    '$sort' => ['created_at' => -1]
                ]      
            ];

            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportOverSpeed(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {
            $search['$match']['alert_status'] = 'Overspeed';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            
            $query = [
                [
                    '$project' => array(
                        'license_plate'   => '$license_plate',
                        'created_at'      => '$created_at',
                        'vin'             => '$vehicle_number',
                        'machine_number'  => '$machine_number',
                        'address'         => '$last_location',
                        'speed'           => '$speed',
                        'over_speed_time' => '$over_speed_time',
                        'category_over_speed' => '$category_over_speed',
                    )
                ],
                [
                    '$sort' => ['created_at' => -1]
                ]
            ];
            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportUnPlugged(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {  
            $search['$match']['vehicle_status'] = 'Unppluged';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte . " 00:00:00")*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte . " 24:00:00")*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            
            $query = [
            [
                '$project' => array(
                    'geofence_area'  => 'ambil dari mana nih?', //ambil dari mana?
                    'poi'            => 'ambil dari mana nih?', //ambil dari mana?
                    'created_at'     => '$created_at',
                    'license_plate'  => '$license_plate',
                    'vin'            => '$vehicle_number',
                    'longitude'      => '$longitude',
                    'latitude'       => '$latitude',
                    'alert'          => '$alert_status',
                    'address'        => '$last_location',
                )
            ]];
            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    private function filters($request){
        if(!$request->has('license_plate') && !$request->has('startDate') && !$request->has('endDate')){
            throw new \Exception("Filter is required.");
        }
    }

 }