<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoGpsNotUpdateOneDay;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use App\Models\RptUtilization;
use Carbon\Carbon;
use App\Helpers;

class HistoryFleetUtilization extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'history_fleet_utilization:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'History for report fleet utilization';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */

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
			ini_set("memory_limit",-1);
			ini_set('max_execution_time', 0);
			
			//get mapping to collect license_plate
			$mwMapping = MwMapping::get()->toArray();
			if(!empty($mwMapping)) foreach($mwMapping as $mwMapping){
				$date      = Carbon::now()->subDays(1)->format('Y-m-d');
				$startDate = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 00:00:00')*1000);
				$endDate   = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 23:59:59')*1000);
				// $getHistory = MwMappingHistory::select('license_plate', 'vehicle_number', 'ignition','speed', 'device_time','total_odometer','fuel_consumed', 'vehicle_status')
				$getHistory = MwMappingHistory::whereBetween('device_time', [$startDate, $endDate])
											  ->where('license_plate', $mwMapping['license_plate'])
											  ->orderBy('device_time', 'asc')
											  ->get();
				
				// print_r($getHistory->toArray());die();

				$count = $getHistory->count();
				//Park Time Initialize
				$counterParkTime  = $durationParkTime = $isParkTime = $odoMeterPark = $fuelComsumedPark = 0;
				//Moving Time Initialize
				$counterMovingTime = $durationMovingTime = $isMovingTime = $odoMeterMoving = $fuelComsumedMoving = $speedMoving = 0;
				//Engine Idle Time Initialize
				$counterEngineIdleTime = $durationIdleTime = $isEngineIdleTime = $odoMeterIdle =  $fuelComsumedIdle = 0;
				
				if(!empty($getHistory)) foreach($getHistory->toArray() as $key => $hsty){
					/**
					 * FOR PARK TIME
					 */
					if(($hsty['ignition'] == 0 && $hsty['speed'] == 0) && ($hsty['vehicle_status'] != 'Unpluged')){
						if($counterParkTime > 0){
							$isParkTime = 1;
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
														->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('ignition', 0)
														->where('speed', 0)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first();
							if(!empty($lastRow)){
								$start = Carbon::parse($lastRow->device_time);
								$durationParkTime += ($start->diffInSeconds($hsty['device_time']));
								echo $hsty['device_time']. ' - '. $start." ";
								echo $start->diffInSeconds($hsty['device_time'])."\n";
							}
							$odoMeterPark      = $hsty['total_odometer'];
							$fuelComsumedPark  = $hsty['fuel_consumed'];

							//jika row terakhir ispark == true
							if(($key == ($count - 1))){
								//store in rpt_utilization
								$checking = RptUtilization::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
								if(empty($checking->first())){
									$hsty['speed'] = 0;
									$hsty['ignition'] = 0;
									$hsty['park_time'] = $durationParkTime;
									$hsty['total_odometer'] = $odoMeterPark;
									$hsty['fuel_consumed'] = $fuelComsumedPark;
									RptUtilization::create($hsty);
									echo 'Total Park Time = '.$durationParkTime."\n";
								}
								// Reset counterParkTime, durationParkTIme, isParkTime
								$counterParkTime = $durationParkTime = $isParkTime = $odoMeterPark = $fuelComsumedPark = 0;
							}
						}else{
							$counterParkTime++;
						}

					}else{
						//get status != park dari  (status park = true secara berturut2)
						if($isParkTime){
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
													    ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('ignition', 0)
														->where('speed', 0)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first();
							if(!empty($lastRow)){
								$start = Carbon::parse($lastRow->device_time);
								$durationParkTime += ($start->diffInSeconds($hsty['device_time']));
								echo $hsty['device_time']. ' - '. $start." ";
								echo $start->diffInSeconds($hsty['device_time'])."\n";
							}
							$odoMeterPark      = $hsty['total_odometer'];
							$fuelComsumedPark  = $hsty['fuel_consumed'];

							//store in rpt_utilization
							$hsty['park_time'] = $durationParkTime;
							$checking = RptUtilization::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
							if(empty($checking->first())){
								$hsty['speed'] = 0;
								$hsty['ignition'] = 0;
								$hsty['park_time'] = $durationParkTime;
								$hsty['total_odometer'] = $odoMeterPark;
								$hsty['fuel_consumed'] = $fuelComsumedPark;
								RptUtilization::create($hsty);
								echo 'Total Park Time = '.$durationParkTime."\n";
							}
							// Reset counterParkTime, durationParkTIme, isParkTime
							$counterParkTime = $durationParkTime = $isParkTime = $odoMeterPark = $fuelComsumedPark = 0;
						
						}
						
					}

					/**
					 * FOR MOVING TIME
					 */
					if(($hsty['ignition'] == 1 && $hsty['speed'] > 0)  && ($hsty['vehicle_status'] != 'Unpluged')){
						if($counterMovingTime > 0){
							$isMovingTime = 1;
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
														->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('ignition', '1')
														->where('speed', '>', 0)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first();
							if(!empty($lastRow)){
								$start = Carbon::parse($lastRow->device_time);
								$durationMovingTime += ($start->diffInSeconds($hsty['device_time']));
								echo $hsty['device_time']. ' - '. $start." ";
								echo $start->diffInSeconds($hsty['device_time'])."\n";
							}
							$odoMeterMoving      = $hsty['total_odometer'];
							$fuelComsumedMoving  = $hsty['fuel_consumed'];
							$speedMoving 		+= $hsty['speed'];
							
							//jika row terakhir isMoving == true
							if(($key == ($count - 1))){
								//store in rpt_utilization
								$checking = RptUtilization::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
								if(empty($checking->first())){
									$hsty['speed'] 			= Helpers::speedCalculation($speedMoving, $counterMovingTime+1); // Harus di sum nih kayaknya karna speed harus > 0, klo diambil last nya ternya speednya 0 kan jadi kaco
									$hsty['ignition'] 		= 1;
									$hsty['moving_time'] 	= $durationMovingTime;
									$hsty['total_odometer'] = $odoMeterMoving;
									$hsty['fuel_consumed']  = $fuelComsumedMoving;
									
									RptUtilization::create($hsty);
									echo 'Total Moving Time = '.$durationMovingTime."\n";
								}
								// Reset counterMovingTime, durationMovingTime, isMovingTime
								$counterMovingTime = $durationMovingTime = $isMovingTime = $odoMeterMoving = $fuelComsumedMoving = 0;
							}
						}else{
							$speedMoving += $hsty['speed'];
						}
						$counterMovingTime++;

					}else{
						//get status != moving dari  (status moving = true secara berturut2)
						if($isMovingTime){
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
													    ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('ignition', '1')
														->where('speed', '>', 0)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first();
							if(!empty($lastRow)){
								$start = Carbon::parse($lastRow->device_time);
								$durationMovingkTime += ($start->diffInSeconds($hsty['device_time']));
								echo $hsty['device_time']. ' - '. $start." ";
								echo $start->diffInSeconds($hsty['device_time'])."\n";
							}
							$odoMeterMoving       = $hsty['total_odometer'];
							$fuelComsumedMoving   = $hsty['fuel_consumed'];
							$speedMoving 		 += $hsty['speed'];
							//store in rpt_utilization
							$checking = RptUtilization::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
							if(empty($checking->first())){
								$hsty['speed'] 			= Helpers::speedCalculation($speedMoving, $counterMovingTime+1); // Harus di sum nih kayaknya karna speed harus > 0, klo diambil last nya ternya speednya 0 kan jadi kaco
								$hsty['ignition'] 		= 1;
								$hsty['moving_time'] 	= $durationMovingTime;
								$hsty['total_odometer'] = $odoMeterMoving;
								$hsty['fuel_consumed']  = $fuelComsumedMoving;
								RptUtilization::create($hsty);
								echo 'Total Moving Time = '.$durationMovingTime."\n";
							}
							// Reset counterMovingTime, durationMovingTime, isMovingTime
							$counterMovingTime = $durationMovingTime = $isMovingTime = $odoMeterMoving = $fuelComsumedMoving = 0;
						
						}
						
					}

					/**
					 * FOR ENGINE IDLE
					 */
					if(($hsty['ignition'] == 1 && $hsty['speed'] == 0)  && ($hsty['vehicle_status'] != 'Unpluged')){
						if($counterEngineIdleTime > 0){
							$isEngineIdleTime = 1;
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
														->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('ignition', '1')
														->where('speed', 0)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first();
							if(!empty($lastRow)){
								$start 			   = Carbon::parse($lastRow->device_time);
								$durationIdleTime += ($start->diffInSeconds($hsty['device_time']));
								echo $hsty['device_time']. ' - '. $start." ";
								echo $start->diffInSeconds($hsty['device_time'])."\n";
							}
							$odoMeterIdle 	   = $hsty['total_odometer'];
							$fuelComsumedIdle  = $hsty['fuel_consumed'];

							//jika row terakhir isMoving == true
							if(($key == ($count - 1))){
								//store in rpt_utilization
								$checking = RptUtilization::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
								if(empty($checking->first())){
									$hsty['speed'] 			= 0;
									$hsty['ignition'] 	    = 1;
									$hsty['idle_time'] 	= $durationIdleTime;
									$hsty['total_odometer'] = $odoMeterIdle;
									$hsty['fuel_consumed']  = $fuelComsumedIdle;
									RptUtilization::create($hsty);
									echo 'Total Idle Time = '.$durationIdleTime."\n";
								}
								// Reset counterEngineIdleTime, durationIdleTime, isEngineIdleTime
								$counterEngineIdleTime = $durationIdleTime = $isEngineIdleTime = $fuelComsumedIdle = 0;
							}
						}else{
							$counterEngineIdleTime++;
						}

					}else{
						//get status != idle dari  (status idle = true secara berturut2)
						if($isEngineIdleTime){
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
													    ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('ignition', '1')
														->where('speed', 0)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first();
							if(!empty($lastRow)){
								$start 			   = Carbon::parse($lastRow->device_time);
								$durationIdleTime += ($start->diffInSeconds($hsty['device_time']));
								echo $hsty['device_time']. ' - '. $start." ";
								echo $start->diffInSeconds($hsty['device_time'])."\n";
							}
							$odoMeterIdle 	   = $hsty['total_odometer'];
							$fuelComsumedIdle  = $hsty['fuel_consumed'];
							//store in rpt_utilization
							$checking = RptUtilization::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
							if(empty($checking->first())){
								$hsty['speed'] 			= 0;
								$hsty['ignition'] 	    = 1;
								$hsty['idle_time'] 	= $durationIdleTime;
								$hsty['total_odometer'] = $odoMeterIdle;
								$hsty['fuel_consumed']  = $fuelComsumedIdle;
								RptUtilization::create($hsty);
								echo 'Total Idle Time = '.$durationIdleTime."\n";
							}
							// Reset counterEngineIdleTime, durationIdleTime, isEngineIdleTime
							$counterEngineIdleTime = $durationIdleTime = $isEngineIdleTime = $fuelComsumedIdle = 0;
						
						}
						
					}

				}
			}
			echo 'Success';
		} catch(\Exception $e) {
            print_r($e->getMessage());
		}

  	}
}