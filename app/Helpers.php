<?php
namespace App;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\MwMapping;
use App\Models\TransactionVehiclePair;
use App\Models\MongoMasterStatusVehicle;
use App\Models\MongoMasterStatusEvent;
use App\Models\MongoMasterEventRelated;
use App\Models\MsVehicle;
use App\Models\BestDriver;
use App\Telegram;
use Carbon\Carbon;
use \ZMQContext;
use \ZMQ;
use DB;

Class Helpers{

    public static function dashboardFormat(){
        $result = [
            'showVehicleStatus'         => [],
            'showAlertSummary'          => [],
            'showGPSnotUpdatedOneDay'   => [],
            'showGPSnotUpdatedThreeDay' => [],
            'showTopMileage'            => [],
            'showBestDriver'            => [],
            'showWorstDriver'           => [],
            'showGeofence'              => []
        ];

        //*********************************** showVehicleStatus format ****************************************//
        $showVehicleStatus = MwMapping::raw(function($collection)
        {
            return $collection->aggregate([
                [
                    '$project' => array(
                        '_id' => 0,
                        'vehicle_status' => '$vehicle_status',
                        'vehicle_status_color' => '$vehicle_status_color'
                    )
                ],
                [
                    '$group' => array(
                        '_id' => [
                                'vehicle_status' => '$vehicle_status',
                                'vehicle_status_color' => '$vehicle_status_color',
                            ],
                        'total' => [
                            '$sum' => 1
                        ]
                    )
                ],
                [
                    '$group' => array(
                        '_id' => 0,
                        'types' => [
                            '$push' => [
                                'vehicle_status' => '$_id.vehicle_status',
                                'vehicle_status_color' => '$_id.vehicle_status_color',
                                'total' => '$total'
                            ]
                        ],
                        "grandTotal" => [
                            '$sum' => '$total'
                        ]
                    )
                ],
                [
                    '$unwind' => '$types'
                ],
                [
                    '$project' => array(
                        '_id' => 0,
                        'vehicle_status' => '$types.vehicle_status',
                        'vehicle_status_color' => '$types.vehicle_status_color',
                        'percentage' => [
                            '$multiply' => [[
                                '$divide' => [100, '$grandTotal']
                            ], '$types.total']
                        ]
                    )
                ]


            ]);
        })->toArray();
       
        $n = 0;
        $tempVehicleStatus = [];
        $masterVehicleStatus = MongoMasterStatusVehicle::all()->toArray();
        if(!empty($masterVehicleStatus)){ 
            foreach($masterVehicleStatus as $status){
                $tempVehicleStatus[$n]['vehicle_status'] = $status['status_vehicle_name'];
                $tempVehicleStatus[$n]['vehicle_status_color']= $status['color_hex'];
                $tempVehicleStatus[$n]['percentage'] = null;
                if(!empty($showVehicleStatus)){
                    foreach($showVehicleStatus as $vehicleStatus){
                        if($vehicleStatus['vehicle_status'] == $status['status_vehicle_name']){
                            $tempVehicleStatus[$n]['percentage'] = $vehicleStatus['percentage'];
                        }
                    }
                }
            $n++;
            }
        }
        $result['showVehicleStatus'] = $tempVehicleStatus;


        //*********************************** showAlertSummary format ****************************************//
        // get alert status event
        $showAlertStatus = MwMapping::raw(function($collection)
        {
            return $collection->aggregate([
                [
                    '$match' => [
                        'alert_status' => [
                            '$in' => ['Out Of Zone', 'Overspeed', 'Main Power Remove', 'Signal Jamming']
                        ]
                    ]
                ],
                [
                    '$project' => array(
                        '_id' => 0,
                        'alert_status' => '$alert_status',
                        'status_alert_color_hex' => '$status_alert_color_hex'
                    )
                ],
                [
                    '$group' => array(
                        '_id' => [
                                'alert_status' => '$alert_status',
                                'status_alert_color_hex' => '$status_alert_color_hex',
                            ],
                        'total' => [
                            '$sum' => 1
                        ]
                    )
                ],
                [
                    '$group' => array(
                        '_id' => 0,
                        'types' => [
                            '$push' => [
                                'alert_status' => '$_id.alert_status',
                                'status_alert_color_hex' => '$_id.status_alert_color_hex',
                                'total' => '$total'
                            ]
                        ],
                        "grandTotal" => [
                            '$sum' => '$total'
                        ]
                    )
                ],
                [
                    '$unwind' => '$types'
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'alert_status' => '$types.alert_status',
                        'status_alert_color_hex' => '$types.status_alert_color_hex',
                        'percentage' => [
                            '$multiply' => [[
                                '$divide' => [100, '$grandTotal']
                            ], '$types.total']
                        ]
                    ]
                ]


            ]);
        })->toArray();
        
        $n = 0;
        $tempAlertStatus = [];
        $masterStatusEvent = MongoMasterStatusEvent::get()->toArray();
        if(!empty($masterStatusEvent)) foreach($masterStatusEvent as $masterStatus){
            $tempAlertStatus[$n]['alert_status'] = $masterStatus['status_alert_name'];
            $tempAlertStatus[$n]['status_alert_color_hex']= $masterStatus['status_alert_color_hex'];
            $tempAlertStatus[$n]['percentage'] = null;
            foreach($showAlertStatus as $status){
                if($status['alert_status'] == $masterStatus['status_alert_name']){
                    $tempAlertStatus[$n]['percentage'] = $status['percentage'];
                }
            }
            $n++;
        }
        

        //************************************* get alert priority *******************************************//
         $showAlertPriority = MwMapping::raw(function($collection)
        {
            return $collection->aggregate([
                [
                    '$project' => array(
                        '_id' => 0,
                        'alert_priority' => '$alert_priority',
                        'alert_priority_color' => '$alert_priority_color'
                    )
                ],
                [
                    '$group' => array(
                        '_id' => [
                                'alert_priority' => '$alert_priority',
                                'alert_priority_color' => '$alert_priority_color',
                            ],
                        'total' => [
                            '$sum' => 1
                        ]
                    )
                ],
                [
                    '$group' => array(
                        '_id' => 0,
                        'types' => [
                            '$push' => [
                                'alert_priority' => '$_id.alert_priority',
                                'alert_priority_color' => '$_id.alert_priority_color',
                                'total' => '$total'
                            ]
                        ],
                        "grandTotal" => [
                            '$sum' => '$total'
                        ]
                    )
                ],
                [
                    '$unwind' => '$types'
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'alert_priority' => '$types.alert_priority',
                        'alert_priority_color' => '$types.alert_priority_color',
                        'percentage' => [
                            '$multiply' => [[
                                '$divide' => [100, '$grandTotal']
                            ], '$types.total']
                        ]
                    ]
                ]


            ]);
        })->toArray();
        
        $n=0;
        $tempAlertPriority = [];
        $masterAlertPriority = self::masterAlertPriority();
        if(!empty($masterAlertPriority)) foreach($masterAlertPriority as $priority){
            $tempAlertPriority[$n]['alert_priority'] = $priority['alert_priority_name'];
            $tempAlertPriority[$n]['alert_priority_color']= $priority['alert_priority_color_hex'];
            $tempAlertPriority[$n]['percentage'] = null;
            if(!empty($showAlertPriority)){
                
                foreach($showAlertPriority as $status){
                    
                    if($status['alert_priority'] == $priority['alert_priority_name']){
                        $tempAlertPriority[$n]['percentage'] = $status['percentage'];
                    }
                }
            }
            $n++;
        }

        $result['showAlertSummary'] = array_merge($tempAlertPriority, $tempAlertStatus);

        
        //************************************* showGPSnotUpdatedOneDay ****************************************//
        $max24hours = Carbon::now()->subHours(24);
        $max72hours = Carbon::now()->subHours(72);
        $showGPSnotUpdatedOneDay = MwMapping::where('updated_at', '>=', $max24hours)
                                                      ->where('updated_at', '<', $max72hours)
                                                      ->orderBy('updated_at', 'desc')
                                                      ->take(10)->get()->toArray();

        if(!empty($showGPSnotUpdatedOneDay)) foreach($showGPSnotUpdatedOneDay as $val){
            $result['showGPSnotUpdatedOneDay'][] = [
                                                    'license_plate' => $val['license_plate'],
                                                    'last_updated' => $val['updated_at'],
                                                    'duration' => Carbon::parse($val['updated_at'])->diffForHumans()
            ];
        }
        

        //*********************************** showGPSnotUpdatedThreeDay start ***********************************//
        $threeDaysAgo = Carbon::now()->subDays(3);
        $showGPSnotUpdatedThreeDay = MwMapping::where('updated_at', '<=', $threeDaysAgo)
                                                        ->orderBy('updated_at', 'desc')
                                                        ->take(10)->get()->toArray();

        if(!empty($showGPSnotUpdatedThreeDay)) foreach($showGPSnotUpdatedThreeDay as $val){
            $result['showGPSnotUpdatedThreeDay'][] = [
                                                    'license_plate' => $val['license_plate'],
                                                    'last_updated' => $val['updated_at'],
                                                    'duration' => Carbon::parse($val['updated_at'])->diffForHumans()
            ];
        }


        //**************************************** showTopMileage format ****************************************//
        $result['showTopMileage'] = MwMapping::where('created_at', '<=', Carbon::today())
                                             ->orderBy('total_odometer', 'desc')
                                             ->take(10)->get()->toArray();

        
        //**************************************** showBestDriver format ****************************************//
        $result['showBestDriver'] = BestDriver::where('created_at', '>=', Carbon::today())
                                              ->orWhere('updated_at', '>=', Carbon::today())
                                              ->orderBy('score', 'desc')
                                              ->take(10)->get()->toArray();
        

        //**************************************** showWorstDriver format ****************************************//
        $result['showWorstDriver'] = BestDriver::where('created_at', '>=', Carbon::today())
                                               ->orWhere('updated_at', '>=', Carbon::today())
                                               ->orderBy('score', 'asc')
                                               ->take(10)->get()->toArray();


        //**************************************** showGeofence format *******************************************//                                          
        $result['showGeofence'] = MwMapping::select('license_plate', 'duration_out_zone')
                                           ->where('is_out_zone', true)
                                           ->take(10)->get()->toArray();

        return $result;
    }

    public static function allTrackingFormat(){
        $tracking = MwMapping::get()->toArray();
        return $tracking;
    }

    public static function singleTrackingFormat($license_plate = null){
        $tracking = MwMapping::where('license_plate', $license_plate)->first()->toArray();
        return $tracking;
    }

    public static function masterAlertPriority(){
        return [
            ['alert_priority_name' => 'Critical' , 'alert_priority_color_hex' => '#ff0033'],
            ['alert_priority_name' => 'Warning' , 'alert_priority_color_hex' => '#ffcc33'],
            ['alert_priority_name' => 'Information' , 'alert_priority_color_hex' => '#0033cc'],
        ];
    }

    public static function sendToClient($pushData){
        $context = new ZMQContext();
        $socket  = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://".env('ZMQ_HOST').":".env('ZMQ_TCP_PORT'));
        $socket->send(json_encode($pushData));
    }

    public static function updateToSync($model=null, $id){
        // This Logic For update Date in Vehicle Pair
        if (empty($model)) {
            $now = Carbon::now()->toDateTimeString();
            $updateVehiclePair = TransactionVehiclePair::where('vehicle_code', $id)->update(['updated_at' => $now]);
        }

        if ($model == 'zone') {
            $data = MsVehicle::where('area_code', $id)->first();
            $now = Carbon::now()->toDateTimeString();
            $updateVehiclePair = TransactionVehiclePair::where('vehicle_code', $id)->update(['updated_at' => $now]);
        }
    }
   
    public static function sendTelegram($param){
        $token  = "765886508:AAGZVU3GqgxRtWqlAnLOIihYr77R_0eV-ko";	
        $chatId = "-265247766";

        $txt  ="<strong>Gpstracking:</strong>\n";
        $txt .="No.Pol : ".$param['license_plate']." | ";
        $txt .="Waktu : ".Carbon::parse($param['device_time'])->format('Y-m-d H:i:s')." | ";
        $txt .="Alert : ".$param['alert_status']." | ";
        $txt .="Lokasi : ".$param['last_location']." | ";
        $txt .= "https://www.google.co.id/maps/place/".$param['latitude'].",".$param['longitude'];
        
        $telegram = new Telegram($token);
        $telegram->sendMessage($chatId, $txt, 'HTML');
    }
}
