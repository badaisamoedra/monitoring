<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoGpsNotUpdateThreeDay;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use Carbon\Carbon;

class GpsNotUpdateThreeDay extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'gps_not_update_three_day:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Store data not update three day';

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
			$threeDaysAgo =  new \MongoDB\BSON\UTCDatetime(strtotime(Carbon::now()->subDays(3))*1000);
			$showGPSnotUpdatedThreeDay = MwMapping::where('device_time', '<', $threeDaysAgo)
															->orderBy('device_time', 'desc')
															->get()->toArray();
			$n=1;
            if(!empty($showGPSnotUpdatedThreeDay)) foreach($showGPSnotUpdatedThreeDay as $data){
				$checkDuplicate = MongoGpsNotUpdateThreeDay::where('imei', $data['imei'])
														 ->where('last_update', new \MongoDB\BSON\UTCDatetime(strtotime($data['device_time'])*1000))
														 ->first();
				if(empty($checkDuplicate)){										 
					//insert to table gps_not_update_three_day
					$data['category']    = 'Three Days';
					$data['last_update'] = new \MongoDB\BSON\UTCDatetime(strtotime($data['device_time'])*1000);
					if(!empty($data) && isset($data['_id'])){
						unset($data['_id']);
						unset($data['updated_at']);
						unset($data['created_at']);
					} 
					MongoGpsNotUpdateThreeDay::create($data);
					echo $n++."\n";
				}
			}
 		} catch(\Exception $e) {
            print_r($e->getMessage());
		}
  	}
}