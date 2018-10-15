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

    public function reportDriverScore(){
        $this->filters($request);
        $data = RptDriverScoring::raw(function($collection) use ($request)
        {  
            $search['$match']['vehicle_status'] = 'Unppluged';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('startDate')){
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
                    'vin'            => '$vehicle_number',
                    'longitude'      => '$longitude',
                    'latitude'       => '$latitude',
                    'speed'          => '$speed',
                    'alert'          => '$alert_status',
                    'address'        => '$last_location',
                )
            ]];
            return $collection->aggregate(array_merge([$search], $query));
        });
        return $this->makeResponse(200, 1, null, $data);
    }

    public function reportFleetUtilisation(){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {  
            $search['$match']['vehicle_status'] = 'Unppluged';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('startDate')){
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
                    'vin'            => '$vehicle_number',
                    'longitude'      => '$longitude',
                    'latitude'       => '$latitude',
                    'speed'          => '$speed',
                    'alert'          => '$alert_status',
                    'address'        => '$last_location',
                )
            ]];
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
            
            if($request->has('startDate') || $request->has('startDate')){
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
           
            if($request->has('startDate') || $request->has('startDate')){
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
                    'heading'      => '$direction',
                    // sleepmode (deep sleep)
                    // immo 
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

    public function reportKMDriven(){
        $data = $this->globalCrudRepo->all();
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
            
            if($request->has('startDate') || $request->has('startDate')){
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
            
            if($request->has('startDate') || $request->has('startDate')){
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
                        // GeofenceArea

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
            $search['$match'] = [];
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
            
            if($request->has('startDate') || $request->has('startDate')){
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
                        'created_at'      => '$created_at',
                        'vin'            => '$vehicle_number',
                        'machine_number' => '$machine_number',
                        // Duration
                        // KategoriOverspeed
                        'speed'          => '$speed',
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

    public function reportUnPlugged(Request $request){
        $this->filters($request);
        $data = MwMappingHistory::raw(function($collection) use ($request)
        {  
            $search['$match']['vehicle_status'] = 'Unppluged';
            if($request->has('license_plate') && !empty($request->license_plate)){
              $search['$match']['license_plate'] = $request->license_plate;
            }
           
            if($request->has('startDate') || $request->has('startDate')){
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
                    'vin'            => '$vehicle_number',
                    'longitude'      => '$longitude',
                    'latitude'       => '$latitude',
                    'speed'          => '$speed',
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