<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use App\Models\RptOverSpeed;
use Carbon\Carbon;

class HistoryOverSpeedTwo extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'history_over_speed_two:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'History for report over speed > 100';

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
				$date      = Carbon::now()->format('Y-m-d');
				$startDate = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 00:00:00')*1000);
				$endDate   = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 23:59:59')*1000);
				$getHistory = MwMappingHistory::whereBetween('device_time', [$startDate, $endDate])
											->where('license_plate', $mwMapping['license_plate'])
											->orderBy('device_time', 'asc')
											->get();
				$n 			 = 0;
				$count       = $getHistory->count();
				$duration    = 0;
				$isOverSpeed = 0;
				// For overspeed > 100
				if(!empty($getHistory)) foreach($getHistory->toArray() as $hsty){
					if($hsty['alert_status'] == 'Sampling'){
						if($hsty['speed'] > 100){
							if($n > 0){
								$isOverSpeed = 1;
								$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
														->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('speed', '>', 100)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first()->toArray();
								$start = Carbon::parse($lastRow['device_time']);
								$duration += ($start->diffInSeconds($hsty['device_time']));
							}

							//jika row terakhir overspeed == true
							if(($n == ($count - 1))){
								//store in rpt_over_speed
								$hsty['over_speed_time'] = $duration;
								$hsty['category_over_speed'] = "> 100";
								RptOverSpeed::create($hsty);
							}
						}else{
							//get status overspeed > 100 dari  (status overspeed 80 - 100 secara berturut2)
							if($isOverSpeed){
								$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
															->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
															->where('speed', '>', 100)
															->where('license_plate', $hsty['license_plate'])
															->orderBy('device_time', 'desc')->first()->toArray();
								$start = Carbon::parse($lastRow['device_time']);
								$duration+= ($start->diffInSeconds($hsty['device_time']));
								
								//store in rpt_over_speed
								$hsty['over_speed_time'] = $duration;
								$hsty['category_over_speed'] = "> 100";
								RptOverSpeed::create($hsty);
							}
							$isOverSpeed = 0;
						}
						
					}else{
						//get status not overspeed dari  (status overspeed = true secara berturut2)
						if($isOverSpeed){
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'speed')
													    ->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
														->where('speed', '>', 100)
														->where('license_plate', $hsty['license_plate'])
														->orderBy('device_time', 'desc')->first()->toArray();
							$start = Carbon::parse($lastRow['device_time']);
							$duration+= ($start->diffInSeconds($hsty['device_time']));
							
							//store in rpt_over_speed
							$hsty['over_speed_time'] = $duration;
							$hsty['category_over_speed'] = "> 100";
							RptOverSpeed::create($hsty);
						}
						$isOverSpeed = 0;
					}
				
					echo $n."\n";
					$n++;
				}

			}
			echo 'Success';
		} catch(\Exception $e) {
            print_r($e->getMessage());
		}
  	}
}