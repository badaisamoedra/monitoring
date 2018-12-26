<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use App\Models\RptOverSpeed;
use App\Models\MongoLogsReport;
use Carbon\Carbon;
use App\Helpers;

class HistoryOverSpeed extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'history_over_speed:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'History for report over speed 80 - 100';

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


     /**
      * OverSpeedOne = 80 - 100
      * OverSpeedTwo = > 100
      */
	public function handle()
	{
        try{
			ini_set("memory_limit",-1);
            ini_set('max_execution_time', 0);
            
			$date      = Carbon::now()->format('Y-m-d');
            $startDate = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 00:00:00')*1000);
            $endDate   = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 23:59:59')*1000);
            
            //get mapping to collect license_plate
            $mwMapping = MwMappingHistory::whereBetween('device_time', [$startDate, $endDate])
                                         ->where('speed', '>=', 80)
                                         ->groupBy('imei')->get(['license_plate'])->toArray();
         
			if(!empty($mwMapping)) foreach($mwMapping as $mwMapping){
				$getHistory = MwMappingHistory::whereBetween('device_time', [$startDate, $endDate])
											->where('license_plate', $mwMapping['license_plate'])
											->orderBy('device_time', 'asc')
                                            ->get();

                $count  = $getHistory->count();
                //OverspeedOne Initialize
                $counterOverSpeedOne = $durationOverSpeedOne = $isOverSpeedOne  = 0;
                //OverspeedTwo Initialize
                $counterOverSpeedTwo = $durationOverSpeedTwo = $isOverSpeedTwo  = 0;
                
                if(!empty($getHistory)) foreach($getHistory->toArray() as $key => $hsty){

                    /**
                     * FOR OVERSPEEDO 80 - 100
                     */
                    if($hsty['speed'] >= '80' && $hsty['speed'] <= 100){   
                        $isOverSpeedOne = 1;
                        if($counterOverSpeedOne > 0){
                            $lastRow = MwMappingHistory::select('imei','license_plate','device_time', 'speed')
                                                        ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
                                                        ->whereBetween('speed', [80, 100])
                                                        ->where('license_plate', $hsty['license_plate'])
                                                        ->orderBy('device_time', 'desc')->first();

                            if(!empty($lastRow)){
                                $start = Carbon::parse($lastRow->device_time);
                                $durationOverSpeedOne += ($start->diffInSeconds($hsty['device_time']));
                                echo $hsty['device_time']. ' - '. $start." ";
                                echo $start->diffInSeconds($hsty['device_time'])."\n";
                            }

                            //jika row terakhir isOverSpeedOne == true
							if(($key == ($count - 1))){
								//store in rpt_over_speed
								$checking = RptOverSpeed::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
								if(empty($checking->first())){
									$hsty['over_speed_time'] = $durationOverSpeedOne;
									$hsty['category_over_speed'] = "80 - 100";
									RptOverSpeed::create($hsty);
									echo 'Total OverSpeedOne Time = '.$durationOverSpeedOne."\n";
								}
								// Reset counterOverSpeedOne, durationOverSpeedOne, isOverSpeedOne
								$counterOverSpeedOne = $durationOverSpeedOne = $isOverSpeedOne  = 0;
							}
                        }else{
                            $counterOverSpeedOne++;
                        }
                    }else{
                        //get isOverSpeedOne == false dari  (isOverSpeedOne = true secara berturut2)
                        if($isOverSpeedOne){
                            $lastRow = MwMappingHistory::select('imei','license_plate','device_time', 'speed')
                                                        ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
                                                        ->whereBetween('speed', [80, 100])
                                                        ->where('license_plate', $hsty['license_plate'])
                                                        ->orderBy('device_time', 'desc')->first();

                            if(!empty($lastRow)){
                                $start = Carbon::parse($lastRow->device_time);
                                $durationOverSpeedOne += ($start->diffInSeconds($hsty['device_time']));
                                echo $hsty['device_time']. ' - '. $start." ";
                                echo $start->diffInSeconds($hsty['device_time'])."\n";
                            }

							//store in rpt_over_speed
                            $checking = RptOverSpeed::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
                            if(empty($checking->first())){
                                $hsty['over_speed_time'] = $durationOverSpeedOne;
                                $hsty['category_over_speed'] = "80 - 100";
                                RptOverSpeed::create($hsty);
                                echo 'Total OverSpeedOne Time = '.$durationOverSpeedOne."\n";
                            }
                            // Reset counterOverSpeedOne, durationOverSpeedOne, isOverSpeedOne
                            $counterOverSpeedOne = $durationOverSpeedOne = $isOverSpeedOne  = 0;
                        }
                    }


                    /**
                     * FOR OVERSPEED > 100
                     */
                    if($hsty['speed'] > 100){   
                        $isOverSpeedTwo = 1;
                        if($counterOverSpeedTwo > 0){
                            $lastRow = MwMappingHistory::select('imei','license_plate','device_time', 'speed')
                                                        ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
                                                        ->where('speed', '>', 100)
                                                        ->where('license_plate', $hsty['license_plate'])
                                                        ->orderBy('device_time', 'desc')->first();

                            if(!empty($lastRow)){
                                $start = Carbon::parse($lastRow->device_time);
                                $durationOverSpeedTwo += ($start->diffInSeconds($hsty['device_time']));
                                echo $hsty['device_time']. ' - '. $start." ";
                                echo $start->diffInSeconds($hsty['device_time'])."\n";
                            }

                            //jika row terakhir isOverSpeedTwo == true
							if(($key == ($count - 1))){
								//store in rpt_over_speed
								$checking = RptOverSpeed::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
								if(empty($checking->first())){
									$hsty['over_speed_time'] = $durationOverSpeedTwo;
									$hsty['category_over_speed'] = "> 100";
									RptOverSpeed::create($hsty);
									echo 'Total OverSpeedTwo Time = '.$durationOverSpeedTwo."\n";
								}
								// Reset counterOverSpeedTwo, durationOverSpeedTwo, isOverSpeedTwo
                                $counterOverSpeedTwo = $durationOverSpeedTwo = $isOverSpeedTwo  = 0;
							}
                        }else{
                            $counterOverSpeedTwo++;
                        }
                    }else{
                        //get isOverSpeedTwo == false dari  (isOverSpeedTwo = true secara berturut2)
                        if($isOverSpeedTwo){
                            $lastRow = MwMappingHistory::select('imei','license_plate','device_time', 'speed')
                                                        ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
                                                        ->where('speed', '>', 100)
                                                        ->where('license_plate', $hsty['license_plate'])
                                                        ->orderBy('device_time', 'desc')->first();

                            if(!empty($lastRow)){
                                $start = Carbon::parse($lastRow->device_time);
                                $durationOverSpeedTwo += ($start->diffInSeconds($hsty['device_time']));
                                echo $hsty['device_time']. ' - '. $start." ";
                                echo $start->diffInSeconds($hsty['device_time'])."\n";
                            }

							//store in rpt_over_speed
                            $checking = RptOverSpeed::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
                            if(empty($checking->first())){
                                $hsty['over_speed_time'] = $durationOverSpeedTwo;
                                $hsty['category_over_speed'] = "80 - 100";
                                RptOverSpeed::create($hsty);
                                echo 'Total OverSpeedTwo Time = '.$durationOverSpeedTwo."\n";
                            }
                            // Reset counterOverSpeedTwo, durationOverSpeedTwo, isOverSpeedTwo
                            $counterOverSpeedTwo = $durationOverSpeedTwo = $isOverSpeedTwo  = 0;
                        }
                    }
                    
                }

			}
			echo 'Success';
		} catch(\Exception $e) {
            Helpers::logSchedulerReport('ERROR', 'Scheduler History OverSpeed', $e->getMessage());
		}
  	}
}