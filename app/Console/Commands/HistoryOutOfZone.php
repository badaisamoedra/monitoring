<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use App\Models\RptOutOfZone;
use Carbon\Carbon;

class HistoryOutOfZone extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'history_out_of_zone:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'History for report out of zone';

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
				$date      = Carbon::now()->subHours(24)->format('Y-m-d');
				$startDate = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 00:00:00')*1000);
				$endDate   = new \MongoDB\BSON\UTCDatetime(strtotime($date.' 23:59:59')*1000);
				$getHistory = MwMappingHistory::whereBetween('device_time', [$startDate, $endDate])
											->where('license_plate', $mwMapping['license_plate'])
											->orderBy('device_time', 'asc')
											->get();
											
				$n 			 = 0;
				$count       = $getHistory->count();
				$duration    = 0;
				$isOutOfZone = 0;
				if(!empty($getHistory)) foreach($getHistory->toArray() as $key => $hsty){
					if($hsty['is_out_zone']){
						if($n > 0){
							$isOutOfZone = 1;
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'is_out_zone')
													->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
													->where('is_out_zone', true)
													->where('license_plate', $hsty['license_plate'])
													->orderBy('device_time', 'desc')->first()->toArray();
							$start = Carbon::parse($lastRow['device_time']);
							$duration += ($start->diffInSeconds($hsty['device_time']));
						}

						//jika row terakhir is_out_zone == true
						if(($key == ($count - 1))){
							//store in rpt_out_of_zone
							$checking = RptOutOfZone::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
							if(empty($checking->first())){
								$hsty['out_zone_time'] = $duration;
								RptOutOfZone::create($hsty);
							}
						}
						
					}else{
						//get is_out_zone yg bernilai false dari  (out of zone = true secara berturut2)
						if($isOutOfZone){
							$lastRow = MwMappingHistory::select('license_plate','device_time', 'is_out_zone')
													->where('device_time' , '<', new \MongoDB\BSON\UTCDatetime(strtotime($hsty['device_time'])*1000))
													->where('license_plate', $hsty['license_plate'])
													->orderBy('device_time', 'desc')->first()->toArray();
							$start = Carbon::parse($lastRow['device_time']);
							$duration+= ($start->diffInSeconds($hsty['device_time']));
							
							//store in rpt_out_of_zone
							$checking = RptOutOfZone::where('imei', $hsty['imei'])->where('device_time', Helpers::stringToBson($hsty['device_time']));
							if(empty($checking->first())){
								$hsty['out_zone_time'] = $duration;
								RptOutOfZone::create($hsty);
							}
						}
						$isOutOfZone = 0;
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