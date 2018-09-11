<?php
namespace App;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\MwMapping;
use App\Models\TransactionVehiclePair;
use App\Models\MsVehicle;
use \ZMQContext;
use \ZMQ;
use Carbon\Carbon;

Class Helpers{

    public static function dashboardFormat(){
        $result = [
            'showVehicleStatus'   => [],
            'showVehicleLocation' => [],
            'showUtilization'     => [],
            'showAssetUsage'      => []
        ];
        $dataMapping = MwMapping::all();

        // showVehicleLocation format
        if(!empty($dataMapping)) foreach($dataMapping as $mapping){
            $result['showVehicleLocation'][] = ['license_plate' => $mapping->license_plate,'location' => $mapping->last_location]; 
        }

        // showUtilization format
        $result['showUtilization']['total_moving_time'] = MwMapping::where('vehicle_status','Moving')->count();
        $result['showUtilization']['total_idle_time']   = MwMapping::where('vehicle_status','!=', 'Moving')->count();
        
        // showAssetUsage format
        $result['showAssetUsage']['total_distance']  = MwMapping::where('vehicle_status','Moving')->count();
        $result['showAssetUsage']['fuel_concumed']   = MwMapping::where('vehicle_status','!=', 'Moving')->count();
    
        return $result;
    }

    public static function sendToClient($pushData){
        $context = new ZMQContext();
        $socket  = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://localhost:".env('ZMQ_TCP_PORT'));
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
