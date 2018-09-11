<?php
namespace App;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\MwMapping;
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

        // $result['showVehicleStatus'] =  MwMapping::get();

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

   
}
