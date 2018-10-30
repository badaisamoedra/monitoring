<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoGpsNotUpdateOneDay;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use Carbon\Carbon;

class GpsNotUpdateOneDay extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'gps_not_update_one_day:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Store data not update one day';

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
			$max72hours = new \MongoDB\BSON\UTCDatetime(strtotime(Carbon::now()->subHours(72))*1000);
			$max24hours = new \MongoDB\BSON\UTCDatetime(strtotime(Carbon::now()->subHours(24))*1000);
			$showGPSnotUpdatedOneDay = MwMapping::whereBetween('device_time', [$max72hours, $max24hours])
														->orderBy('device_time', 'desc')
														->get()->toArray();
			$n = 1;
            if(!empty($showGPSnotUpdatedOneDay)) foreach($showGPSnotUpdatedOneDay as $data){
				$checkDuplicate = MongoGpsNotUpdateOneDay::where('imei', $data['imei'])
														 ->where('last_update', new \MongoDB\BSON\UTCDatetime(strtotime($data['device_time'])*1000))
														 ->first();
				if(empty($checkDuplicate)){
					//insert to table gps_not_update_one_day
					$data['category'] 	 = 'One Day';
					$data['last_update'] = new \MongoDB\BSON\UTCDatetime(strtotime($data['device_time'])*1000);
					if(!empty($data) && isset($data['_id'])){
						unset($data['_id']);
						unset($data['updated_at']);
						unset($data['created_at']);
					} 
					MongoGpsNotUpdateOneDay::create($data);
					echo $n++."\n";
				}
			}
		} catch(\Exception $e) {
            print_r($e->getMessage());
		}
  	}
}