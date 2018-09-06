<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsVehicle;
use App\Models\MsStatusVehicle;
use App\Models\MwMapping;
use App\Models\BestDriver;
use App\Models\RptDriverScoring;
use Auth;
use DB;

class MappingController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
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
            //get license plate
            $vehicle = MsVehicle::where('imei_obd_number', $request->imei)->first();
            if(empty($vehicle)){
                throw new \Exception("Error Processing Request. Cannot define vehicle");	
            }

            $input  = [
                'device_id' => $request->device_id,
                'imei' => $request->imei,
                'device_type' => $request->device_type,
                'device_model' => $request->device_model,
                'vehicle_number' => $request->vehicle_number,
                'priority' => $request->priority,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location' => $request->location,
                'altitude' => $request->altitude,
                'direction' => $request->direction,
                'speed' => $request->speed,
                'satellite' => $request->satellite,
                'accuracy' => $request->accuracy,
                'dtc_number' => $request->dtc_number,
                'lac' => $request->lac,
                'gps_pdop' => $request->gps_pdop,
                'gps_hdop' => $request->gps_hdop,
                'gsm_signal_level' => $request->gsm_signal_level,
                'trip_odometer' => $request->trip_odometer,
                'total_odometer' => $request->total_odometer,
                'external_power_voltage' => $request->external_power_voltage,
                'internal_battery_voltage' => $request->internal_battery_voltage,
                'internal_battery_current' => $request->internal_battery_current,
                'cell_id' => $request->cell_id,
                'pto_state' => $request->pto_state,
                'engine_total_fuel_used' => $request->engine_total_fuel_used,
                'fuel_level_1_x' => $request->fuel_level_1_x,
                'server_time' => $request->server_time,
                'device_time' => $request->device_time,
                'device_timestamp' => $request->device_timestamp,
                'engine_total_hours_of_operation_x' => $request->engine_total_hours_of_operation_x,
                'service_distance' => $request->service_distance,
                'at_least_pto_engaged' => $request->at_least_pto_engaged,
                'eco_driving_type' => $request->eco_driving_type,
                'eco_driving_value' => $request->eco_driving_value,
                'wheel_based_speed' => $request->wheel_based_speed,
                'accelerator_pedal_position' => $request->accelerator_pedal_position,
                'engine_percent_load' => $request->engine_percent_load,
                'engine_speed_x' => $request->engine_speed_x,
                'tacho_vehicle_speed_x' => $request->tacho_vehicle_speed_x,
                'engine_coolant_temperature_x' => $request->engine_coolant_temperature_x,
                'instantaneous_fuel_economy_x' => $request->instantaneous_fuel_economy_x,
                'digital_input_1' => $request->digital_input_1,
                'digital_input_2' => $request->digital_input_2,
                'digital_input_3' => $request->digital_input_3,
                'digital_input_4' => $request->digital_input_4,
                'sensor' => $request->sensor,
                'ignition' => $request->ignition,
                'crash_detection' => $request->crash_detection,
                'geofence_zone_01' => $request->geofence_zone_01,
                'digital_output_1' => $request->digital_output_1,
                'digital_output_2' => $request->digital_output_2,
                'gps_status' => $request->gps_status,
                'movement_sensor' => $request->movement_sensor,
                'data_mode' => $request->data_mode,
                'deep_sleep' => $request->deep_sleep,
                'analog_input_1' => $request->analog_input_1,
                'gsm_operator' => $request->gsm_operator,
                'dallas_temperature_1' => $request->dallas_temperature_1,
                'dallas_temperature_2' => $request->dallas_temperature_2,
                'dallas_temperature_3' => $request->dallas_temperature_3,
                'dallas_temperature_4' => $request->dallas_temperature_4,
                'dallas_id_1' => $request->dallas_id_1,
                'dallas_id_2' => $request->dallas_id_2,
                'dallas_id_3' => $request->dallas_id_3,
                'dallas_id_4' => $request->dallas_id_4,
                'event' => $request->event,
                'event_type_id' => $request->event_type_id,
                'event_type' => $request->event_type,
                'telemetry' => $request->telemetry,
                'reff_id' => $request->reff_id,
                'license_plate' => $vehicle->license_plate,
                'last_location' => $this->getAddress($request->all()),
                'vehicle_status' => $this->vehicleStatus($request->all()),
                // 'vehicle_status_color' => ,
                // 'alert_status' => ,
                // 'alert_satus_color' => ,
                // 'alert_priority' => ,
                // 'alert_priority_color' => ,
                // 'is_out_zone' => ,
                // 'duration_out_zone' => ,
                // 'is_gps_not_update' => ,
                // 'duration_gps_not_update' => ,
                // 'fuel_consumed' => ,
            ];

            //get detail by imei
            $mapping = $this->globalCrudRepo->find('imei',$request->imei);

            if(empty($mapping)){
                //insert
                $data = $this->globalCrudRepo->create($input);
            }else{
                //update
                $data = $this->globalCrudRepo->updateObject($mapping->id, $input);
            }

            return $this->makeResponse(200, 1, null, $data);
        } catch(\Exception $e) {
            return $this->makeResponse(500, 0, $e->getMessage(), null);
        }
       
    }

    private function vehicleStatus($param){
        if($param['ignition']   == 1 && $param['speed'] > 0)  return 'Moving';
        if($param['ignition']   == 1 && $param['speed'] == 0) return 'Stop';
        if($param['ignition']   == 0 && $param['speed'] == 0) return 'Offline';
        if($param['event_type'] == 'MB_CN') return 'Unplugged';
    }

    private function getAddress($param){
        if(!empty($param['latitude']) && !empty($param['longitude'])){
            //Send request and receive json data by address
            $geocodeFromLatLong = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($param['latitude']).','.trim($param['longitude']).'&sensor=false&key='.API_GMAPS); 
            $output = json_decode($geocodeFromLatLong);
            $status = $output->status;
            //Get address from json data
            $address = ($status=="OK")?$output->results[1]->formatted_address:'';
           
            //Return address of the given latitude and longitude
            if(!empty($address)){
                return $address;
            }else{
                return false;
            }
        }else{
            return false;   
        }
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