<?php
namespace App;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\MwMapping;
use App\Models\TransactionVehiclePair;
use App\Models\MsVehicle;
use App\Models\BestDriver;
use Carbon\Carbon;
use \ZMQContext;
use \ZMQ;

Class Helpers{

    public static function dashboardFormat(){
        $result = [
            'showVehicleStatus'   => [],
            'showVehicleLocation' => [],
            'showUtilization'     => [],
            'showAssetUsage'      => [],
            'showBestDriver'      => []
        ];
        $dataMapping = MwMapping::take(10)->get();

        // showVehicleStatus format
        $result['showVehicleStatus'] = MwMapping::raw(function($collection)
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
                    '$project' => [
                        '_id' => 0,
                        'vehicle_status' => '$types.vehicle_status',
                        'vehicle_status_color' => '$types.vehicle_status_color',
                        'percentage' => [
                            '$multiply' => [[
                                '$divide' => [100, '$grandTotal']
                            ], '$types.total']
                        ]
                    ]
                ]


            ]);
        })->toArray();

         // showAlertSummary format
         $result['showAlertSummary'] = MwMapping::raw(function($collection)
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
       
        // showVehicleLocation format (limit 10)
        $result['showVehicleLocation'] = MwMapping::select('license_plate','last_location')->take(10)->get()->toArray();

        // showUtilization format
        $result['showUtilization']['total_moving_time'] = MwMapping::where('vehicle_status','Moving')->count();
        $result['showUtilization']['total_idle_time']   = MwMapping::where('vehicle_status','!=', 'Moving')->count();
        
        // showAssetUsage format
        $result['showAssetUsage']['total_distance']  = MwMapping::sum('total_odometer');
        $result['showAssetUsage']['fuel_concumed']   = MwMapping::sum('fuel_consumed');

        // showBestDriver format
        $result['showBestDriver'] = BestDriver::where('created_at', '>=', Carbon::today())
                                                ->orderBy('score', 'desc')
                                                ->take(10)->get()->toArray();
        return $result;
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
            print_r($data);die();
            $now = Carbon::now()->toDateTimeString();
            $updateVehiclePair = TransactionVehiclePair::where('vehicle_code', $id)->update(['updated_at' => $now]);
        }
    }
   
}
