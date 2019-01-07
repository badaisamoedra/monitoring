<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMappingHistory;
use App\Models\RptOutOfZone;
use App\Models\RptDriverScoring;
use App\Models\MongoMasterEventRelated;
use App\Models\MongoGpsNotUpdateOneDay;
use App\Models\MongoGpsNotUpdateThreeDay;
use App\Models\RptUtilization;
use App\Models\RptOverSpeed;
use Carbon\Carbon;
use Auth;

class ReportController extends BaseController
{

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
        $x = true;

        $data = RptDriverScoring::raw(function($collection) use ($request)
        {  
            $search['$match']['$or'] = [
                ['alert_status' => [
                    '$in' => ['Overspeed','Signal Jamming','Out Of Zone', 'Green Driving', 'Crash', 'Unplug']
                ]],
                ['is_out_zone' => [
                    '$eq' => ['is_out_zone', '1']
                ]]
            ];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            $query = [
            [
                '$project' => array(
                    'license_plate'  => '$license_plate',
                    'driver'         => '$driver_name',
                    'over_speed'     => [
                        '$cond' => [
                            'if'   => [ '$eq' => [ '$alert_status', 'Overspeed' ]],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],
                    'harsh_acceleration' => [ //eco_driving_type = 1
                        '$cond' => [
                            'if'   => [ 
                                '$and' => [
                                    ['$eq' => [ '$alert_status', 'Green Driving' ]],
                                    ['$eq' => [ '$eco_driving_type', '1' ]],
                                ] 
                            ],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],
                    'harsh_braking' => [ //eco_driving_type = 2
                        '$cond' => [
                            'if'   => [ 
                                '$and' => [
                                    ['$eq' => [ '$alert_status', 'Green Driving' ]],
                                    ['$eq' => [ '$eco_driving_type', '2' ]],
                                ]
                            ],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],
                    'harsh_cornering' => [ //eco_driving_type = 3
                        '$cond' => [
                            'if'   => [ 
                                '$and' => [
                                    ['$eq' => [ '$alert_status', 'Green Driving' ]],
                                    ['$eq' => [ '$eco_driving_type', '3' ]],
                                ]
                            ],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],
                    'main_power_cut' => [
                        '$cond' => [
                            'if'   => [ '$eq' => [ '$alert_status', 'Main Power Remove' ]],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],
                    'signal_jamming' => [
                        '$cond' => [
                            'if'   => [ '$eq' => [ '$alert_status', 'Signal Jamming' ]],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],
                    'bump_detection' => [
                        '$cond' => [
                            'if'   => [ '$eq' => [ '$alert_status', 'Crash' ]],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],
                    'out_of_zone' => [ //confirm
                        '$cond' => [
                            'if'   => [ '$eq' => [ '$is_out_zone', 'true' ]],
                            'then' => '1',
                            'else' =>  0
                        ]
                    ],   
                    'alert_status'   => '$alert_status',
                    'total'          => '$score',
                )
            ]];
            return $collection->aggregate(array_merge([$search], $query));
        });

        
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportFleetUtilisation(Request $request){
        $this->filters($request);
        $data = RptUtilization::raw(function($collection) use ($request)
        {  
            $search['$match'] = [];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['device_time'] = $created_at;
            }
            $duration = Carbon::parse($request->startDate);
            $duration = (int) $duration->diffInSeconds($request->endDate);
            $query = [
                [
                    '$group' => array(
                        '_id' => '$imei',
                        'license_plate'     => ['$first' => '$license_plate'],
                        'vehicle_number'    => ['$first' => '$vehicle_number'],
                        'machine_number'    => ['$first' => '$machine_number'],
                        'total_data'        => ['$sum'   => 1],
                        'park_time'         => ['$sum'   => '$park_time'],
                        'moving_time'       => ['$sum'   => '$moving_time'],
                        'idle_time'         => ['$sum'   => '$idle_time'],
                        'total_mileage'     => ['$last'   => '$total_odometer'],
                        'speed'             => ['$sum'   => '$speed'],
                        'fuel'              => ['$sum'   => '$fuel_consumed'],
                        // 'duration_out_zone' => ['$sum'   => '$duration_out_zone'], di take out
                        'start_date'        => ['$first' => '$device_time'],
                        'end_date'          => ['$last'  => '$device_time'],
                    )
                ]
                ,[
                    '$project' => array(
                        '_id'            => 0,
                        'license_plate'     => '$license_plate',
                        'vin'               => '$vehicle_number',
                        'machine_number'    => '$machine_number',
                        'total_data'        => '$total_data',
                        'park_time'         => '$park_time',
                        'moving_time'       => '$moving_time',
                        'idle_time'         => '$idle_time',
                        'fuel'              => '$fuel',
                        'total_mileage'     => '$total_mileage',
                        'engine_on_time'    => ['$add'    => ['$moving_time', '$idle_time']],
                        'duration'          => ['$ifNull' => [0, $duration]],
                        'average_speed'     => ['$divide' => [ '$speed', '$total_data']],
                        'rasio_engine_on'   => 
                                                [
                                                    '$multiply' => [[
                                                        '$divide' => [['$add' => ['$moving_time', '$idle_time']], $duration]
                                                    ], 100],
                                                ],
                        // 'duration_out_zone' => '$duration_out_zone', di take out
                        'start_date'        => '$start_date',
                        'end_date'          => '$end_date'
                    )
                ],
            ];
            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportGpsNotUpdate(Request $request){
        if($request->category == 1){
            // not update <= 1 day
            $data = MongoGpsNotUpdateOneDay::raw(function($collection) use ($request)
            {
                $search['$match'] = [];
                if($request->has('startDate') || $request->has('endDate')){
                   $last_update = [];
                   $gte = $request->has('startDate') ? $request->startDate : '';
                   $lte = $request->has('endDate') ? $request->endDate : '';
                   if(!empty($gte)) $last_update['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
                   if(!empty($lte)) $last_update['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
                   if(!empty($last_update)) $search['$match']['last_update'] = $last_update;
                }
                
                $query = [
                    [
                        '$project' => array(
                            'category'          => '$category',
                            'gps_supplier'      => '$gps_supplier',
                            'branch'            => '$branch',
                            'license_plate'     => '$license_plate',
                            'imei'              => '$imei',
                            'vin'               => '$vehicle_number',
                            'date_installation' => '$date_installation',
                            'last_update'       => '$last_update',
                            'last_location'     => '$last_location',
                            'gps_satellite'     => '$satellite',
                            'gsm_signal'        => '$gsm_signal_level',
                        )
                    ],
                    [
                        '$sort' => ['last_update' => -1]
                    ]
                ];
                return $collection->aggregate(array_merge([$search], $query));
            });

        }else{
            // not update > 3 days
             $data = MongoGpsNotUpdateThreeDay::raw(function($collection) use ($request)
             {
                 $search['$match'] = [];
                 if($request->has('startDate') || $request->has('endDate')){
                    $last_update = [];
                    $gte = $request->has('startDate') ? $request->startDate : '';
                    $lte = $request->has('endDate') ? $request->endDate : '';
                    if(!empty($gte)) $last_update['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
                    if(!empty($lte)) $last_update['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
                    if(!empty($last_update)) $search['$match']['last_update'] = $last_update;
                 }
                 
                 $query = [
                     [
                         '$project' => array(
                             'category'          => '$category',
                             'gps_supplier'      => '$gps_supplier',
                             'branch'            => '$branch',
                             'license_plate'     => '$license_plate',
                             'imei'              => '$imei',
                             'vin'               => '$vehicle_number',
                             'date_installation' => '$date_installation',
                             'last_update'       => '$last_update',
                             'last_location'     => '$last_location',
                             'gps_satellite'     => '$satellite',
                             'gsm_signal'        => '$gsm_signal_level',
                         )
                     ],
                     [
                         '$sort' => ['last_update' => -1]
                     ]
                 ];
                 return $collection->aggregate(array_merge([$search], $query));
             });
        }
       
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
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['device_time'] = $created_at;
            }
           
            $query = [
                [
                '$project' => array(
                    'device_time'    => '$device_time',
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
                    'sleep_mode'     => [
                                        '$cond' => [
                                            'if'   => [ '$eq' => [ '$deep_sleep', 0 ]],
                                            'then' => 'Off',
                                            'else' => 'On'
                                        ]
                    ],
                    'satellite'      => '$satellite',
                    'accu'           => '$internal_battery_voltage',
                    'gsm_signal'     => '$gsm_signal_level',
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
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['device_time'] = $created_at;
            }
            
            $query = [
                [
                '$group' => array(
                        '_id' => '$imei',
                        'license_plate'  => ['$first' => '$license_plate'],
                        'vehicle_number' => ['$first' => '$vehicle_number'],
                        'machine_number' => ['$first' => '$machine_number'],
                        'driver_name'    => ['$first' => '$driver_name'],
                        'start_address'  => ['$first' => '$last_location'],
                        'end_address'    => ['$last'  => '$last_location'],
                        'km_start'       => ['$first' => '$total_odometer'],
                        'km_end'         => ['$last'  => '$total_odometer'],
                    )
                ],
                [
                    '$project' => array(
                        '_id'  => 0,
                        'license_plate'  => '$license_plate',
                        'driver'         => '$driver_name',
                        'vin'            => '$vehicle_number',
                        'machine_number' => '$machine_number',
                        'start_address'  => '$start_address',
                        'end_address'    => '$end_address',
                        'km_start'       => '$km_start',
                        'km_end'         => '$km_end',
                        'km_driven'      => ['$subtract' => ['$km_end', '$km_start']],
                        'total_odometer' => '$km_end',
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
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['device_time'] = $created_at;
            }

            $msAlert = [];
            $msEventRelated = MongoMasterEventRelated::whereNotNull('notification_code')->get()->toArray();
            if(!empty($msEventRelated)){
                foreach($msEventRelated as $alert){
                    $msAlert[] = $alert['alert_name'];
                }
                if(!empty($msAlert)) $search['$match']['alert_status'] = ['$in' => $msAlert];
            }
            
            $query = [
                [
                    '$project' => array(
                        'device_time'    => '$device_time',
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
        $data = RptOutOfZone::raw(function($collection) use ($request)
        {
            $search['$match'] = [];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['device_time'] = $created_at;
            }
           
            $query = [
                [
                    '$group' => array(
                        '_id'               => ['imei'   => '$imei'],
                        'license_plate'     => ['$first' => '$license_plate'],
                        'vehicle_number'    => ['$first' => '$vehicle_number'],
                        'machine_number'    => ['$first' => '$machine_number'],
                        'device_time'       => ['$first' => '$device_time'],
                        'branch'            => ['$first' => '$branch'],
                        'address'           => ['$last'  => '$last_location'],
                        'duration_out_zone' => ['$sum'   => '$out_zone_time'],
                    )
                ],
                [
                    '$project' => array(
                        '_id'            => 0,
                        'license_plate'  => '$license_plate',
                        'device_time'    => '$device_time',
                        'vin'            => '$vehicle_number',
                        'machine_number' => '$machine_number',
                        'address'        => '$address',
                        'duration'       => ['$sum' => '$out_zone_time'],
                        'geofence_area'  => '$branch'
                    )
                ],
                [
                    '$sort' => ['license_plate' => -1]
                ]      
            ];

            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportOverSpeed(Request $request){
        $this->filters($request);
        $data = RptOverSpeed::raw(function($collection) use ($request)
        {
            $search['$match']['alert_status'] = 'Overspeed';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            if($request->has('category_over_speed') && !empty($request->category_over_speed)){
                $search['$match']['category_over_speed'] = trim($request->category_over_speed);
              }
            
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['device_time'] = $created_at;
            }
            
            $query = [
                [
                    '$project' => array(
                        'license_plate'       => '$license_plate',
                        'device_time'         => '$device_time',
                        'vin'                 => '$vehicle_number',
                        'machine_number'      => '$machine_number',
                        'address'             => '$last_location',
                        'speed'               => '$speed',
                        'over_speed_time'     => '$over_speed_time',
                        'category_over_speed' => '$category_over_speed',
                    )
                ],
                [
                    '$sort' => ['device_time' => -1]
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
            $search['$match']['vehicle_status'] = 'Unpluged';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('endDate')){
               $created_at = [];
               $gte = $request->has('startDate') ? $request->startDate : '';
               $lte = $request->has('endDate') ? $request->endDate : '';
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['device_time'] = $created_at;
            }
            
            $query = [
            [
                '$project' => array(
                    'geofence_area'  => ['$ifNull' => [ null, '$geofence_area' ]],
                    'poi'            => ['$ifNull' => [ null, '$poi' ]],
                    'device_time'    => '$device_time',
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