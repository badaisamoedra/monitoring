<?php
namespace App;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\MwMapping;
use App\Models\TransactionVehiclePair;
use App\Models\MongoMasterStatusVehicle;
use App\Models\MongoMasterStatusEvent;
use App\Models\MongoMasterEventRelated;
use App\Models\MsStatusAlertPriority;
use App\Models\MsVehicle;
use App\Models\BestDriver;
use Illuminate\Support\Facades\Mail;
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
                    '$project' => array(
                        'vehicle_status' => '$_id.vehicle_status',
                        'vehicle_status_color' => '$_id.vehicle_status_color',
                        'total' => '$total'
                    )
                ],

            ]);
        })->toArray();
       
        $n = 0;
        $tempVehicleStatus = [];
        $masterVehicleStatus = MongoMasterStatusVehicle::all()->toArray();
        if(!empty($masterVehicleStatus)){ 
            foreach($masterVehicleStatus as $status){
                $tempVehicleStatus[$n]['vehicle_status'] = $status['status_vehicle_name'];
                $tempVehicleStatus[$n]['vehicle_status_color']= $status['color_hex'];
                $tempVehicleStatus[$n]['total'] = null;
                if(!empty($showVehicleStatus)){
                    foreach($showVehicleStatus as $vehicleStatus){
                        if($vehicleStatus['vehicle_status'] == $status['status_vehicle_name']){
                            $tempVehicleStatus[$n]['total'] = $vehicleStatus['total'];
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
                        'alert_priority' => '$alert_priority',
                        'alert_status'   => '$alert_status',
                        'status_alert_color_hex' => '$status_alert_color_hex'
                    )
                ],
                [
                    '$group' => array(
                        '_id' => [
                                'alert_priority' => '$alert_priority',
                            ],
                        'total' => [
                            '$sum' => 1
                        ]
                    )
                ],
                [
                    '$project' => array(
                        '_id' => 0,
                        'alert_priority' => '$_id.alert_priority',
                        'total' => '$total'
                    )
                ]

            ]);
        })->toArray();
       
        $n=0;
        $tempAlertPriority = [];
        $masterAlertPriority = MsStatusAlertPriority::get()->toArray();
        if(!empty($masterAlertPriority)) foreach($masterAlertPriority as $priority){
            $tempAlertPriority[$n]['alert_priority'] = $priority['alert_priority_name'];
            $tempAlertPriority[$n]['alert_priority_color']= $priority['alert_priority_color_hex'];
            $tempAlertPriority[$n]['total'] = 0;
            if(!empty($showAlertStatus)){
                
                foreach($showAlertStatus as $status){
                    if($status['alert_priority'] == $priority['alert_priority_name']){
                        $tempAlertPriority[$n]['total'] = $status['total'];
                    }
                }
            }
            $n++;
        }

        $result['showAlertSummary'] = $tempAlertPriority;

        
        //************************************* showGPSnotUpdatedOneDay ****************************************//
        $max72hours = new \MongoDB\BSON\UTCDatetime(strtotime(Carbon::now()->subHours(72))*1000);
		$max24hours = new \MongoDB\BSON\UTCDatetime(strtotime(Carbon::now()->subHours(24))*1000);
        $showGPSnotUpdatedOneDay = MwMapping::whereBetween('device_time', [$max72hours, $max24hours])
                                                    ->orderBy('device_time', 'desc')
                                                    ->take(10)->get()->toArray();

        if(!empty($showGPSnotUpdatedOneDay)) foreach($showGPSnotUpdatedOneDay as $val){
            $result['showGPSnotUpdatedOneDay'][] = [
                                                    'license_plate' => $val['license_plate'],
                                                    'last_updated'  => $val['device_time'],
                                                    'duration'      => Carbon::now()->diffInSeconds($val['device_time'])
            ];
        }
        

        //*********************************** showGPSnotUpdatedThreeDay start ***********************************//
        $threeDaysAgo = new \MongoDB\BSON\UTCDatetime(strtotime(Carbon::now()->subDays(3))*1000);
        $showGPSnotUpdatedThreeDay = MwMapping::where('device_time', '<', $threeDaysAgo)
                                                        ->orderBy('device_time', 'desc')
                                                        ->take(10)->get()->toArray();

        if(!empty($showGPSnotUpdatedThreeDay)) foreach($showGPSnotUpdatedThreeDay as $val){
            $result['showGPSnotUpdatedThreeDay'][] = [
                                                    'license_plate' => $val['license_plate'],
                                                    'last_updated'  => $val['device_time'],
                                                    'duration'      => Carbon::now()->diffInSeconds($val['device_time'])
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

    public static function pushNotificationFormat($param){
        $txt  ="<strong>Gpstracking:</strong>\n";
        $txt .="No.Pol : ".$param['license_plate']." | ";
        $txt .="Waktu : ".Carbon::parse($param['device_time'])->format('Y-m-d H:i:s')." | ";
        $txt .="Alert : ".$param['alert_status']." | ";
        $txt .="Lokasi : ".$param['last_location']." | ";
        $txt .="https://www.google.co.id/maps/place/".$param['latitude'].",".$param['longitude'];
        return $txt;
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

    public static function sendEmail($param){
        Mail::send('emails.notification', $param, function ($message) use ($param) {
            $message->from('angger.projects@gmail.com', 'GPS System Notice');
            $message->to('angger.dayu@gmail.com');
            $message->subject("GPS System notice --- ".$param['license_plate']." -". $param['alert_status'].".");
           
            $path  = base_path().'/public/listmail.json';
            if(file_exists($path) && !empty(filesize($path))){
                $broadcast = [];
                $listBcc   = json_decode(file_get_contents($path));
                if(!empty($listBcc)) foreach($listBcc as $bc){
                    $broadcast[] = $bc->email; 
                }
                if(!empty($broadcast)) $message->bcc(array_merge($broadcast));
            }
        });
    }

    public static function bsonToString($bsonDate){
        $date = (string) $bsonDate;
        $utcdatetime = new \MongoDB\BSON\UTCDateTime($date);
        $datetime = $utcdatetime->toDateTime();
        return  $datetime->format('Y-m-d H:i:s');
    }

    public static function speedCalculation($totalSpeed, $totalRow){
        if(!empty($totalSpeed)){
            $result = $totalSpeed / $totalRow;
            $result = ($result > 0) ? (int) $result : 0;
        }else{
            $result = 0;
        }
        return $result;
    }
}
