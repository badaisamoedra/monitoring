<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMappingHistory;
use App\Models\RptDriverScoring;
use App\Models\MongoMasterEventRelated;
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
        $data = RptDriverScoring::raw(function($collection) use ($request)
        {  
            $search['$match']['alert_status'] = ['$in' => ['Overspeed','Signal Jamming','Out Of Zone', 'Green Driving', 'Crash', 'Unplug']];
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
                            'if'   => [ '$eq' => [ '$alert_status', 'Out Of Zone' ]],
                            'then' => '$score',
                            'else' =>  0
                        ]
                    ],   
                    // 'immobilizer' => [ //confirm
                    //     '$cond' => [
                    //         'if'   => [ '$eq' => [ '$alert_status', 'Overspeed' ]],
                    //         'then' => '$score',
                    //         'else' =>  0
                    //     ]
                    // ],       
                    // 'un_immobilizer' => [ //confirm
                    //     '$cond' => [
                    //         'if'   => [ '$eq' => [ '$alert_status', 'Overspeed' ]],
                    //         'then' => '$score',
                    //         'else' =>  0
                    //     ]
                    // ], 
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
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
            }
            $duration = Carbon::parse($request->startDate);
            $duration = (int) $duration->diffInMinutes($request->endDate);
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
                        'engine_on_time'    => ['$sum'   => '$engine_on_time'],
                        'total_mileage'     => ['$sum'   => '$total_odometer'],
                        'speed'             => ['$sum'   => '$speed'],
                        'fuel'              => ['$sum'   => 'fuel_consumed'],
                        'duration_out_zone' => ['$sum'   => '$duration_out_zone'],
                    )
                ]
                ,[
                    '$project' => array(
                        '_id'            => 0,
                        'license_plate'  => '$license_plate',
                        'vin'            => '$vehicle_number',
                        'machine_number' => '$machine_number',
                        'total_data'     => '$total_data',
                        'park_time'      => '$park_time',
                        'moving_time'    => '$moving_time',
                        'idle_time'      => '$idle_time',
                        'fuel'           => '$fuel',
                        'engine_on_time' => '$engine_on_time',
                        'total_mileage'  => '$total_mileage',
                        'average_speed'  => [
                            '$divide' => [ '$speed', '$total_data']
                        ],
                        'rasio_engine_on' => [
                            '$divide' => ['$engine_on_time', $duration]
                        ],
                        'duration_out_zone' => '$duration_out_zone'
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

            if($request->has('kategori') && !empty($request->kategori)){
                if($request->kategori == 1){
                    // not update <= 1 day

                }else{
                    // not update > 3 days
                }
                // $search['$match']['kategori'] = $request->kategori;
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
                        'gps_supplier'   => [
                            '$ifNull' => ['$gps_supplier', "PT Blue Chip Transland / Teltonika"]
                        ],
                        'branch'   => [
                            '$ifNull' => [ '$branch', null ]
                        ],
                        'license_plate'  => '$license_plate',
                        'imei'           => '$imei',
                        'vin'            => '$vehicle_number',
                        'install_date'   => '$date_installation',
                        'created_at'     => '$created_at',
                        'last_location'  => '$last_location',
                        'gps_satellite'  => '$satellite',
                        'gsm_signal'     => '$gsm_signal_level',
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
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
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
                    'sleep_mode'           => [
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
               if(!empty($gte)) $created_at['$gte'] = new \MongoDB\BSON\UTCDatetime(strtotime($gte)*1000);
               if(!empty($lte)) $created_at['$lte'] = new \MongoDB\BSON\UTCDatetime(strtotime($lte)*1000);
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
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
               if(!empty($created_at)) $search['$match']['created_at'] = $created_at;
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
            $search['$match']['is_out_zone'] = ['$in' => [true , false]];
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
                    '$group' => array(
                        '_id' => [
                            'imei' => '$imei',
                            'is_out_zone' => '$is_out_zone',
                        ],
                        'license_plate'     => ['$first' => '$license_plate'],
                        'vehicle_number'    => ['$first' => '$vehicle_number'],
                        'machine_number'    => ['$first' => '$machine_number'],
                        'created_at'        => ['$first' => '$created_at'],
                        'branch'            => ['$first' => '$branch'],
                        'address'           => ['$last'  => '$last_location'],
                        'duration_out_zone' => ['$sum'   => '$duration_out_zone'],
                        'duration_in_zone'  => ['$sum'   => '$duration_in_zone']
                    )
                ],
                [
                    '$project' => array(
                        '_id'            => 0,
                        'license_plate'  => '$license_plate',
                        'created_at'     => '$created_at',
                        'vin'            => '$vehicle_number',
                        'machine_number' => '$machine_number',
                        'address'        => '$address',
                        'is_out_zone'    => '$_id.is_out_zone',
                        'duration'       => [
                            '$cond' => [
                                'if'   => ['$eq'    => [ '$_id.is_out_zone', true ]],
                                'then' => ['$sum'   => '$duration_out_zone'],
                                'else' => ['$sum'   => '$duration_in_zone'],
                            ]
                        ],
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
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {
            $search['$match']['alert_status'] = 'Overspeed';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            if($request->has('category_over_speed') && !empty($request->category_over_speed)){
                $search['$match']['category_over_speed'] = $request->category_over_speed;
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
            $search['$match']['vehicle_status'] = 'Unplugged';
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
                    'geofence_area'  => '$branch',
                    'poi'            => [
                        '$ifNull' => [ null, "ambil dari mana" ] //ambil dari mana?
                    ],
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