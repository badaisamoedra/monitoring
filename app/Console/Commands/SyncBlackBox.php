<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Helpers;
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
use Carbon\Carbon;

class SyncBlackBox extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'get_black_box:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Store data black box';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
    static protected $temp;

	public function __construct(GlobalCrudRepo $globalCrudRepo)
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try{
            $date      = Carbon::now()->format('Y-m-d');
            $startDate = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 00:00:00')*1000);
            $endDate   = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 23:59:59')*1000);
            $blackBox  = DB::connection('mongodb_mw')->collection('device_message')
                                                    ->whereBetween('device_time',[$startDate, $endDate] )
                                                    ->where('black_box', true)
                                                    ->get();
            $n = 1;
            if(!empty($blackBox)) foreach($blackBox as $bx){
                
                $bx = (object) $bx;
                // get vehicle
                $vehicle = MongoMasterVehicleRelated::where('vehicle.imei_obd_number', $bx->imei)->first();
                if(empty($vehicle)){
                    continue;
                }
                // get detail by imei
                self::$temp = [
                    'gps_supplier'             => 'PT Blue Chip Transland / Teltonika',
                    'branch'                   => $vehicle['vehicle']['area']['area_name'],
                    'device_id'                => $bx->device_id,
                    'imei'                     => $bx->imei,
                    'device_type'              => $bx->device_type,
                    'device_model'             => $bx->device_model,
                    'vehicle_number'           => $bx->vehicle_number,
                    'priority'                 => $bx->priority,
                    'latitude'                 => $bx->latitude,
                    'longitude'                => $bx->longitude,
                    'location'                 => $bx->location,
                    'altitude'                 => $bx->altitude,
                    'direction'                => $bx->direction,
                    'speed'                    => $bx->speed,
                    'satellite'                => $bx->satellite,
                    'accuracy'                 => $bx->accuracy,
                    'dtc_number'               => $bx->dtc_number,
                    'lac'                      => $bx->lac,
                    'gps_pdop'                 => $bx->gps_pdop,
                    'gps_hdop'                 => $bx->gps_hdop,
                    'gsm_signal_level'         => $bx->gsm_signal_level,
                    'trip_odometer'            => $bx->trip_odometer,
                    'total_odometer'           => $bx->total_odometer,
                    'external_power_voltage'   => $bx->external_power_voltage,
                    'internal_battery_voltage' => $bx->internal_battery_voltage,
                    'internal_battery_current' => $bx->internal_battery_current,
                    'cell_id'                  => $bx->cell_id,
                    'pto_state'                => $bx->pto_state,
                    'engine_total_fuel_used'   => $bx->engine_total_fuel_used,
                    'fuel_level_1_x'           => $bx->fuel_level_1_x,
                    'server_time'              => $bx->server_time,
                    'device_time'              => $bx->device_time,
                    'device_timestamp'         => $bx->device_timestamp,
                    'engine_total_hours_of_operation_x' => $bx->engine_total_hours_of_operation_x,
                    'service_distance'         => $bx->service_distance,
                    'at_least_pto_engaged'     => $bx->at_least_pto_engaged,
                    'eco_driving_type'         => $bx->eco_driving_type,
                    'eco_driving_value'        => $bx->eco_driving_value,
                    'wheel_based_speed'        => $bx->wheel_based_speed,
                    'accelerator_pedal_position' => $bx->accelerator_pedal_position,
                    'engine_percent_load'      => $bx->engine_percent_load,
                    'engine_speed_x'           => $bx->engine_speed_x,
                    'tacho_vehicle_speed_x'    => $bx->tacho_vehicle_speed_x,
                    'engine_coolant_temperature_x' => $bx->engine_coolant_temperature_x,
                    'instantaneous_fuel_economy_x' => $bx->instantaneous_fuel_economy_x,
                    'digital_input_1'          => $bx->digital_input_1,
                    'digital_input_2'          => $bx->digital_input_2,
                    'digital_input_3'          => $bx->digital_input_3,
                    'digital_input_4'          => $bx->digital_input_4,
                    'sensor'                   => $bx->sensor,
                    'ignition'                 => $bx->ignition,
                    'crash_detection'          => $bx->crash_detection,
                    'geofence_zone_01'         => $bx->geofence_zone_01,
                    'digital_output_1'         => $bx->digital_output_1,
                    'digital_output_2'         => $bx->digital_output_2,
                    'gps_status'               => $bx->gps_status,
                    'movement_sensor'          => $bx->movement_sensor,
                    'data_mode'                => $bx->data_mode,
                    'deep_sleep'               => $bx->deep_sleep,
                    'analog_input_1'           => $bx->analog_input_1,
                    'gsm_operator'             => $bx->gsm_operator,
                    'dallas_temperature_1'     => $bx->dallas_temperature_1,
                    'dallas_temperature_2'     => $bx->dallas_temperature_2,
                    'dallas_temperature_3'     => $bx->dallas_temperature_3,
                    'dallas_temperature_4'     => $bx->dallas_temperature_4,
                    'dallas_id_1'              => $bx->dallas_id_1,
                    'dallas_id_2'              => $bx->dallas_id_2,
                    'dallas_id_3'              => $bx->dallas_id_3,
                    'dallas_id_4'              => $bx->dallas_id_4,
                    'event'                    => $bx->event,
                    'event_type_id'            => $bx->event_type_id,
                    'event_type'               => $bx->event_type,
                    'telemetry'                => $bx->telemetry,
                    'reff_id'                  => $bx->reff_id,
                    'driver_name'              => $vehicle['driver']['name'],
                    'date_installation'        => $vehicle['vehicle']['date_installation'],
                    'license_plate'            => $vehicle['vehicle']['license_plate'],
                    'machine_number'           => $vehicle['vehicle']['machine_number'],
                    'simcard_number'           => $vehicle['vehicle']['simcard_number'],
                    'fuel_consumed'            => $bx->total_odometer / $vehicle['vehicle']['model']['fuel_ratio'], 
                    'vehicle_description'      => $vehicle['vehicle']['brand']['brand_vehicle_name'].' '.$vehicle['vehicle']['model']['model_vehicle_name'].' '.$vehicle['vehicle']['year_of_vehicle'],
                    // 'moving_time'              => 0,
                    // 'engine_on_time'           => 0,
                    // 'idle_time'                => 0,
                    // 'park_time'                => 0,
                    // 'over_speed_time'          => 0,
                    'category_over_speed'      => null,
                    'is_out_zone'              => false,
                    'duration_out_zone'        => 0,
                    'duration_in_zone'         => 0,
                    'black_box'                => true
                ];
                // additional field
                self::getAddress($bx);
                self::vehicleStatus($bx, $vehicle);
                self::alertStatus($bx);
                self::checkZone($vehicle, $bx);
                self::bestDriver($vehicle, $bx);
                //store in mapping_history
                MwMappingHistory::create(self::$temp);
                echo $n;
                $n++;
            }
        } catch(\Exception $e) {
            print_r($e->getMessage());
        }
    }
      
    private function getAddress($param){
        if(!empty($param->latitude) && !empty($param->longitude)){
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

    private function vehicleStatus($param, $vehicle){
       
        if($param->ignition == 1) {
            if($param->ignition == 1 && $param->speed > 0) {
                self::$temp['vehicle_status'] = 'Moving';
            }
        } 
        
        if($param->ignition == 1) {
            if($param->ignition == 1 && $param->speed == 0){
                self::$temp['vehicle_status'] = 'Stop';
            }
        }

        if($param->ignition == 0 && $param->speed == 0){
            self::$temp['vehicle_status'] = 'Offline';
        }
       
        if($param->event_type == 'MB_CN'){
            self::$temp['vehicle_status'] = 'Unpluged';
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
        $mongoMsEventRelated = MongoMasterEventRelated::where('provision_alert_name', $param->event_type)->first();
        if(!empty($mongoMsEventRelated)){ 
            self::$temp['alert_status']   = $mongoMsEventRelated->alert_name;
            self::$temp['status_alert_color_hex'] = $mongoMsEventRelated->status_alert_color_hex;
            self::$temp['alert_priority'] = $mongoMsEventRelated->priority_detail['alert_priority_name'];
            self::$temp['alert_priority_color'] = $mongoMsEventRelated->priority_detail['alert_priority_color_hex'];

            // if alert_status = Overspeed then insert duration
            if($mongoMsEventRelated->alert_name == 'Overspeed'){
                if($param->speed >= 80 && $param->speed <= 100)
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

    private function checkZone($vehicle, $param){
        
        $polygon = [];
        $now = Carbon::now();
        $deviceTime = Carbon::parse(Helpers::bsonToString($param->device_time));
        $point      = array($param->latitude, $param->longitude);
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
         self::$temp['duration_in_zone']  = $now->diffInSeconds($deviceTime);
       }else{
         self::$temp['zone_name'] = $zoneName;
         self::$temp['is_out_zone'] = TRUE;
         self::$temp['duration_out_zone'] = $now->diffInSeconds($deviceTime);
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

    public function bestDriver($vehicle, $param){
        $mongoMsEventRelated = MongoMasterEventRelated::where('provision_alert_name', $param->event_type)->first();
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

}