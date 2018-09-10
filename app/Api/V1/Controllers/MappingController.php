<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Helpers;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsVehicle;
use App\Models\MsStatusVehicle;
use App\Models\MwMapping;
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

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        try {

            // get license plate
            $vehicle = MongoMasterVehicleRelated::where('vehicle.imei_obd_number', $request->imei)->first();
            if(empty($vehicle)){
                throw new \Exception("Error Processing Request. Cannot define vehicle");	
            }

            // get detail by imei
            $mapping = $this->globalCrudRepo->find('imei',$request->imei);
            
            self::$temp = [
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
                'license_plate'            => $vehicle['vehicle']['license_plate'],
                'fuel_consumed'            => $request->total_odometer / $vehicle['vehicle']['model']['fuel_ratio'], 
            ];
            
            // additional field
            self::getAddress($request->all());
            self::vehicleStatus($request->all());
            self::alertStatus($request->all());
            self::checkZone($vehicle, $mapping, $request->all());
           
            // if data empty then do insert, if not empty do update
            if(empty($mapping)){
                //insert
                $data = $this->globalCrudRepo->create(self::$temp);
            }else{
                //update
                $data = $this->globalCrudRepo->updateObject($mapping->id, self::$temp);
            }

            // send data to client that subscribe
            $pushData = ['topic' => 'dashboard', 'data' => Helpers::dashboardFormat()];
            Helpers::sendToClient($pushData);

            return $this->makeResponse(200, 1, null, $data);
        } catch(\Exception $e) {
            return $this->makeResponse(500, 0, $e->getMessage(), null);
        }
       
    }

    private function vehicleStatus($param){
        if($param['ignition'] == 1 && $param['speed'] > 0)  
            self::$temp['vehicle_status'] = 'Moving';

        if($param['ignition'] == 1 && $param['speed'] == 0) 
            self::$temp['vehicle_status'] = 'Stop';

        if($param['ignition'] == 0 && $param['speed'] == 0) 
           self::$temp['vehicle_status'] = 'Offline';

        if($param['event_type'] == 'MB_CN')
            self::$temp['vehicle_status'] = 'Unplugged';
        
        //get vehicle status color
        if(isset(self::$temp['vehicle_status'])){
            $msStatusVehicle = MongoMasterStatusVehicle::where('status_vehicle_name', self::$temp['vehicle_status'])->first();
            if(!empty($msStatusVehicle)) 
                self::$temp['vehicle_status_color'] = $msStatusVehicle->color_hex;
            else 
                self::$temp['vehicle_status_color'] = null;
        }
    }

    private function alertStatus($param){
        $mongoMsEventRelated = MongoMasterEventRelated::where('alert_name', $param['event_type'])->first();
        if(!empty($mongoMsEventRelated)){ 
            self::$temp['alert_status']   = $mongoMsEventRelated->alert_name;
            self::$temp['alert_priority'] = $mongoMsEventRelated->priority_detail['alert_priority_name'];
            self::$temp['alert_priority_color'] = $mongoMsEventRelated->priority_detail['alert_priority_color_hex'];
        }else{ 
            self::$temp['alert_status'] = null;
            self::$temp['alert_priority'] = null;
            self::$temp['alert_priority_color'] = null;
        }
    }

    private function getAddress($param){
        if(!empty($param['latitude']) && !empty($param['longitude'])){
            //Send request and receive json data by address
            $geocodeFromLatLong = file_get_contents('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.trim($param['latitude']).'&lon='.trim($param['longitude']).'&limit=1&email=badai.samoedra@gmail.com'); 
            $output = json_decode($geocodeFromLatLong);
            //Get address from json data
            $address = !empty($output) ? $output->display_name:'';
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
        // $deviceTime = Carbon::parse($mapping->device_time);
        $deviceTime = $param['device_time'];

        if(isset($vehicle['vehicle']['zone']) && !empty($vehicle['vehicle']['zone'])){
            foreach($vehicle['vehicle']['zone'] as $zone){
                if(!empty($zone)) foreach($zone['zone_detail'] as $detail){
                    $polygon[] = [$detail['latitude'], $detail['longitude']];
                }
            }
        }

       $point = array($param['latitude'], $param['longitude']);
       if($this->checkPolygon($point, $polygon)){
         self::$temp['is_out_zone'] = FALSE;
         self::$temp['duration_out_zone'] = null;
       }else{
         self::$temp['is_out_zone'] = TRUE;
         self::$temp['duration_out_zone'] = $now->diffInMinutes($deviceTime);
       }
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

    public function bestDriver($param){
        $data = [
            'license_plate' => $param['license_plate'],
            'driver_name'   => $param['driver_name'],
            'score'         => $param['score']
        ];
        $bestDriver = BestDriver::create($data);
        return $bestDriver;
    }

    public function driverScoring($param){
         $data = [
            'driver_code' => $param['driver_code'],
            'driver_name' => $param['driver_name'],
            'alert_type'  => $param['alert_type'],
            'score'       => $param['score'],
        ];
        $driverScoring = RptDriverScoring::create($data);
        return $driverScoring;
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->findObject($id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $update = $this->globalCrudRepo->updateObject($id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->deleteObject($id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}