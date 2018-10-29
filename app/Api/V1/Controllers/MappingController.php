<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Helpers;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsVehicle;
use App\Models\MsStatusVehicle;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use App\Models\Topic;
use App\Models\BestDriver;
use App\Models\RptDriverScoring;
use App\Models\MongoMasterVehicleRelated;
use App\Models\MongoMasterStatusVehicle;
use App\Models\MongoMasterStatusEvent;
use App\Models\MongoMasterEventRelated;
use Auth;
use DB;
use Carbon\Carbon;
use \ZMQContext;
use \ZMQ;


class MappingController extends BaseController
{
    static protected $temp;

    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        self::$temp = [];

        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MwMapping());
    }

    public function index(Request $request)
    {
        if($request->has('search'))
            $data = MwMapping::where('license_plate', 'like', "%$request->search%")->get();
        else
            $data = MwMapping::all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        try {
            // get vehicle
            $vehicle = MongoMasterVehicleRelated::where('vehicle.imei_obd_number', $request->imei)->first();
            if(empty($vehicle)){
                throw new \Exception("Error Processing Request. Cannot define vehicle");	
            }
          
            // get detail by imei
            $mapping = $this->globalCrudRepo->find('imei', $request->imei);
            self::$temp = [
                'gps_supplier'             => 'PT Blue Chip Transland / Teltonika',
                'branch'                   => $vehicle['vehicle']['area']['area_name'],
                'device_id'                => $request->device_id,
                'imei'                     => $request->imei,
                'device_type'              => $request->device_type,
                'device_model'             => $request->device_model,
                'vehicle_number'           => $request->vehicle_number,
                'priority'                 => $request->priority,
                'latitude'                 => $request->latitude,
                'longitude'                => $request->longitude,
                'location'                 => $request->location,
                'altitude'                 => $request->altitude,
                'direction'                => $request->direction,
                'speed'                    => $request->speed,
                'satellite'                => $request->satellite,
                'accuracy'                 => $request->accuracy,
                'dtc_number'               => $request->dtc_number,
                'lac'                      => $request->lac,
                'gps_pdop'                 => $request->gps_pdop,
                'gps_hdop'                 => $request->gps_hdop,
                'gsm_signal_level'         => $request->gsm_signal_level,
                'trip_odometer'            => $request->trip_odometer,
                'total_odometer'           => $request->total_odometer,
                'external_power_voltage'   => $request->external_power_voltage,
                'internal_battery_voltage' => $request->internal_battery_voltage,
                'internal_battery_current' => $request->internal_battery_current,
                'cell_id'                  => $request->cell_id,
                'pto_state'                => $request->pto_state,
                'engine_total_fuel_used'   => $request->engine_total_fuel_used,
                'fuel_level_1_x'           => $request->fuel_level_1_x,
                'server_time'              => $request->server_time,
                'device_time'              => $request->device_time,
                'device_timestamp'         => $request->device_timestamp,
                'engine_total_hours_of_operation_x' => $request->engine_total_hours_of_operation_x,
                'service_distance'         => $request->service_distance,
                'at_least_pto_engaged'     => $request->at_least_pto_engaged,
                'eco_driving_type'         => $request->eco_driving_type,
                'eco_driving_value'        => $request->eco_driving_value,
                'wheel_based_speed'        => $request->wheel_based_speed,
                'accelerator_pedal_position' => $request->accelerator_pedal_position,
                'engine_percent_load'      => $request->engine_percent_load,
                'engine_speed_x'           => $request->engine_speed_x,
                'tacho_vehicle_speed_x'    => $request->tacho_vehicle_speed_x,
                'engine_coolant_temperature_x' => $request->engine_coolant_temperature_x,
                'instantaneous_fuel_economy_x' => $request->instantaneous_fuel_economy_x,
                'digital_input_1'          => $request->digital_input_1,
                'digital_input_2'          => $request->digital_input_2,
                'digital_input_3'          => $request->digital_input_3,
                'digital_input_4'          => $request->digital_input_4,
                'sensor'                   => $request->sensor,
                'ignition'                 => $request->ignition,
                'crash_detection'          => $request->crash_detection,
                'geofence_zone_01'         => $request->geofence_zone_01,
                'digital_output_1'         => $request->digital_output_1,
                'digital_output_2'         => $request->digital_output_2,
                'gps_status'               => $request->gps_status,
                'movement_sensor'          => $request->movement_sensor,
                'data_mode'                => $request->data_mode,
                'deep_sleep'               => $request->deep_sleep,
                'analog_input_1'           => $request->analog_input_1,
                'gsm_operator'             => $request->gsm_operator,
                'dallas_temperature_1'     => $request->dallas_temperature_1,
                'dallas_temperature_2'     => $request->dallas_temperature_2,
                'dallas_temperature_3'     => $request->dallas_temperature_3,
                'dallas_temperature_4'     => $request->dallas_temperature_4,
                'dallas_id_1'              => $request->dallas_id_1,
                'dallas_id_2'              => $request->dallas_id_2,
                'dallas_id_3'              => $request->dallas_id_3,
                'dallas_id_4'              => $request->dallas_id_4,
                'event'                    => $request->event,
                'event_type_id'            => $request->event_type_id,
                'event_type'               => $request->event_type,
                'telemetry'                => $request->telemetry,
                'reff_id'                  => $request->reff_id,
                'driver_name'              => $vehicle['driver']['name'],
                'date_installation'        => $vehicle['vehicle']['date_installation'],
                'license_plate'            => $vehicle['vehicle']['license_plate'],
                'machine_number'           => $vehicle['vehicle']['machine_number'],
                'simcard_number'           => $vehicle['vehicle']['simcard_number'],
                'fuel_consumed'            => $request->total_odometer / $vehicle['vehicle']['model']['fuel_ratio'], 
                'vehicle_description'      => $vehicle['vehicle']['brand']['brand_vehicle_name'].' '.$vehicle['vehicle']['model']['model_vehicle_name'].' '.$vehicle['vehicle']['year_of_vehicle'],
                'moving_time'              => 0,
                'engine_on_time'           => 0,
                'idle_time'                => 0,
                'park_time'                => 0,
                'over_speed_time'          => 0,
                'category_over_speed'      => null,
                'is_out_zone'              => false,
                'duration_out_zone'        => 0,
                'duration_in_zone'         => 0
            ];
            
            // additional field
            self::getAddress($request->all());
            self::vehicleStatus($request->all(), $vehicle);die();
            self::alertStatus($request->all());
            self::checkZone($vehicle, $mapping, $request->all());
            self::bestDriver($vehicle, $request->all());
           
            // if data empty then do insert, if not empty do update
            $checkMapping = $this->globalCrudRepo->find('imei', $request->imei);
            if(empty($checkMapping)){
                //insert
                $data = $this->globalCrudRepo->create(self::$temp);
            }else{
                //update
                $data = $this->globalCrudRepo->updateObject($mapping->id, self::$temp);
            }

            // insert to history
            MwMappingHistory::create(self::$temp);

            // send data to client that subscribe dashboard
            if(Topic::where('name','dashboard')->first()){
                $pushData = ['topic' => 'dashboard', 'data' => Helpers::dashboardFormat()];
                Helpers::sendToClient($pushData);
            }

            // send data to client that subscibe tracking-all
            if(Topic::where('name','tracking-all')->first()){
                $pushData = ['topic' => 'tracking-all', 'data' => Helpers::allTrackingFormat()];
                Helpers::sendToClient($pushData);
            }

            // send data to client that subscribe single tracking
            if(Topic::where('name', self::$temp['license_plate'])->first()){
                $pushData = ['topic' => self::$temp['license_plate'], 'data' => Helpers::singleTrackingFormat(self::$temp['license_plate'])];
                Helpers::sendToClient($pushData);
            }

            return $this->makeResponse(200, 1, null, $data);
        } catch(\Exception $e) {
            return $this->makeResponse(500, 0, $e->getMessage(), null);
        }
       
    }

    private function vehicleStatus($param, $vehicle){
        if($param['ignition'] == 1) {
            if($param['ignition'] == 1 && $param['speed'] > 0) {
                self::$temp['vehicle_status'] = 'Moving';
                self::$temp['moving_time'] = $this->checkDuration($param);
            } else {
                self::$temp['engine_on_time'] = $this->checkDuration($param);
            }
        } 

        if($param['ignition'] == 1) {
            if($param['ignition'] == 1 && $param['speed'] == 0){
                self::$temp['vehicle_status'] = 'Stop';
                self::$temp['idle_time'] = $this->checkDuration($param);
            } else {
                self::$temp['engine_on_time'] = $this->checkDuration($param);
            }
        }

        if($param['ignition'] == 0 && $param['speed'] == 0){
            self::$temp['vehicle_status'] = 'Offline';
            self::$temp['park_time'] = $this->checkDuration($param);
        }
            
        if($param['event_type'] == 'MB_CN'){
            self::$temp['vehicle_status'] = 'Unplugged';
            //set poi
            $zoneName = null;
            $point    = array(self::$temp['latitude'], self::$temp['longitude']);
            if(isset($vehicle['vehicle']['zone']) && !empty($vehicle['vehicle']['zone'])){
                foreach($vehicle['vehicle']['zone'] as $zone){
                    if(!empty($zone) && ($zone['type_zone'] == 'POOL')){
                        foreach($zone['zone_detail'] as $detail){
                            $polygon[] = [$detail['latitude'], $detail['longitude']];
                        }
                        if($this->checkPolygon($point, $polygon)){
                            //set zone name
                            $zoneName = $zone['zone_name'];
                            break;
                        }
                    }
                }
            }
            self::$temp['poi'] = $zoneName;
        }

        // get vehicle status color
        if(isset(self::$temp['vehicle_status'])){
            $msStatusVehicle = MongoMasterStatusVehicle::where('status_vehicle_name', self::$temp['vehicle_status'])->first();
            if(!empty($msStatusVehicle)) 
                self::$temp['vehicle_status_color'] = $msStatusVehicle->color_hex;
            else 
                self::$temp['vehicle_status_color'] = null;
        }
    }

    private function alertStatus($param){
        $mongoMsEventRelated = MongoMasterEventRelated::where('provision_alert_name', $param['event_type'])->first();
        if(!empty($mongoMsEventRelated)){ 
            self::$temp['alert_status']   = $mongoMsEventRelated->alert_name;
            self::$temp['status_alert_color_hex'] = $mongoMsEventRelated->status_alert_color_hex;
            self::$temp['alert_priority'] = $mongoMsEventRelated->priority_detail['alert_priority_name'];
            self::$temp['alert_priority_color'] = $mongoMsEventRelated->priority_detail['alert_priority_color_hex'];

            // if alert_status = Overspeed then insert duration
            if($mongoMsEventRelated->alert_name == 'Overspeed'){
                self::$temp['over_speed_time'] = $this->checkDuration($param);
                if($param['speed'] >= 80 && $param['speed'] <= 100)
                    self::$temp['category_over_speed'] = '80 >= 100';
                else 
                    self::$temp['category_over_speed'] = '> 100';
            }
            // send telegram
            if(isset($mongoMsEventRelated['notif_detail']) && !empty($mongoMsEventRelated['notif_detail'])){
                $notif = explode(',', $mongoMsEventRelated['notif_detail']['notification_code']);
                if(in_array('NTF-0001', $notif)) 
                    Helpers::sendTelegram(self::$temp);
            }
        }else{ 
            self::$temp['alert_status'] = null;
            self::$temp['status_alert_color_hex'] = null;
            self::$temp['alert_priority'] = null;
            self::$temp['alert_priority_color'] = null;
        }
    }

    private function getAddress($param){
        if(!empty($param['latitude']) && !empty($param['longitude'])){
            //Send request and receive json data by address
            // $geocodeFromLatLong = file_get_contents('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.trim($param['latitude']).'&lon='.trim($param['longitude']).'&limit=1&email=badai.samoedra@gmail.com'); 
            
            // $output = json_decode($geocodeFromLatLong);
            //Get address from json data
            // $address = !empty($output) ? $output->display_name:'';
            $address = "Limit Get Address Please Buy";
            //Return address of the given latitude and longitude
            if(!empty($address))
               self::$temp['last_location'] = $address;
            else
               self::$temp['last_location'] = null;
            
        }else{
            self::$temp['last_location'] = null;
        }
    }

    private function checkZone($vehicle, $mapping, $param){
        
        $polygon = [];
        $now = Carbon::now();
        $deviceTime = Carbon::parse($param['device_time']);
        $point      = array($param['latitude'], $param['longitude']);
        $zoneName   = null;
        if(isset($vehicle['vehicle']['zone']) && !empty($vehicle['vehicle']['zone'])){
            foreach($vehicle['vehicle']['zone'] as $zone){
                if(!empty($zone) && ($zone['type_zone'] == 'OUT')){
                    $zoneName = $zone['zone_name'];
                    foreach($zone['zone_detail'] as $detail){
                        $polygon[] = [$detail['latitude'], $detail['longitude']];
                    }
                }
            }
        }
       if($this->checkPolygon($point, $polygon)){
         self::$temp['zone_name'] = $zoneName;
         self::$temp['is_out_zone'] = FALSE;
         self::$temp['duration_in_zone']  = $now->diffInMinutes($deviceTime);
       }else{
         self::$temp['zone_name'] = $zoneName;
         self::$temp['is_out_zone'] = TRUE;
         self::$temp['duration_out_zone'] = $now->diffInMinutes($deviceTime);
       }
    }

    private function checkDuration($param){
        $now = Carbon::now();
        $deviceTime = Carbon::parse($param['device_time']);
        return $now->diffInMinutes($deviceTime);
    }

    private function checkPolygon($point, $polygon){
        if($polygon[0] != $polygon[count($polygon) - 1])
            $polygon[count($polygon)] = $polygon[0];
        
        $j = 0;
        $oddNodes = false;
        $x = $point[1];
        $y = $point[0];
        $n = count($polygon);

        for($i = 0; $i < $n; $i++){
            $j++;
            if($j == $n){
                $j = 0;
            }

            if ((($polygon[$i][0] < $y) && ($polygon[$j][0] >= $y)) || (($polygon[$j][0] < $y) && ($polygon[$i][0] >=
            $y))){
                if ($polygon[$i][1] + ($y - $polygon[$i][0]) / ($polygon[$j][0] - $polygon[$i][0]) * ($polygon[$j][1] -
                    $polygon[$i][1]) < $x){
                    $oddNodes = !$oddNodes;
                }
            }
        }
        return $oddNodes;
    }

    public function bestDriver($vehicle, $param){
        $mongoMsEventRelated = MongoMasterEventRelated::where('provision_alert_name', $param['event_type'])->first();
        if(empty($mongoMsEventRelated)) $score = 0;
        else $score = $mongoMsEventRelated->score;
         
        $bestDriver = new BestDriver;
        $check = $bestDriver->where('driver_name', $vehicle['driver']['name'])->first();
        $oldScore = isset($check->score) ?  $check->score : 0;

        $data = [
            'license_plate' => self::$temp['license_plate'],
            'driver_name'   => $vehicle['driver']['name'],
            'score'         => $score + $oldScore
        ];

        if(!empty($check)){
            // update
            foreach($data as $key => $val){
                $check->{$key} = $val;
            }
            $bestDriver = $check->save();
        }else{
            // insert
            $bestDriver = $bestDriver->create($data);
        }
       

        //insert to rpt_driver_scoring
        self::driverScoring($vehicle, $score);
        
        return $bestDriver;
    }

    public function driverScoring($vehicle, $score){
         $data = [
            'driver_code'      => $vehicle['driver']['driver_code'],
            'driver_name'      => $vehicle['driver']['name'],
            'license_plate'    => self::$temp['license_plate'],
            'alert_status'     => self::$temp['alert_status'],
            'eco_driving_type' => self::$temp['eco_driving_type'],
            'is_out_zone'      => (bool) self::$temp['is_out_zone'],
            'score'            => $score,
            
        ];

        $driverScoring = RptDriverScoring::create($data);
        return $driverScoring;
    }

    public function show(Request $request, $id){
        $data = $this->globalCrudRepo->findObject($id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id){
        $input = $request->all();
        $update = $this->globalCrudRepo->updateObject($id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id){
        $delete = $this->globalCrudRepo->deleteObject($id);
        return $this->makeResponse(200, 1, null, $delete);
    }

    public function getTotalVehicleStatus(){
        $data = MwMapping::raw(function($collection)
        {
            return $collection->aggregate([
                [
                    '$group' => array(
                        '_id' =>  '$vehicle_status',
                        'total' => [
                            '$sum' => 1
                        ]
                    )
                ]

            ]);
        })->toArray();

        $n = 0;
        $result = [];
        $masterStatusVehicle = MongoMasterStatusVehicle::get()->toArray();
        if(!empty($masterStatusVehicle)) foreach($masterStatusVehicle as $msStatus){
            $result[$n]['statusVehicle'] = $msStatus['status_vehicle_name'];
            $result[$n]['total'] = 0;
            foreach($data as $dt){
                if($dt['_id'] == $msStatus['status_vehicle_name']){
                    $result[$n]['total'] = $dt['total'];
                }
            }
            $n++;
        }
        
        return $this->makeResponse(200, 1, null, $result);
    }
    
}