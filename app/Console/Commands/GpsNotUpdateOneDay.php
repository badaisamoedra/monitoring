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
		// $this->globalCrudRepo = $globalCrudRepo;
		// $this->globalCrudRepo->setModel(new MongoGpsNotUpdateOneDay());
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try{
			$max24hours = Carbon::now()->subHours(24);
			$max72hours = Carbon::now()->subHours(72);
			$showGPSnotUpdatedOneDay = MwMapping::where('updated_at', '>=', $max72hours)
														->where('updated_at', '<', $max24hours)
														->orderBy('updated_at', 'desc')
														->get()->toArray();
            if(!empty($showGPSnotUpdatedOneDay)) foreach($showGPSnotUpdatedOneDay as $data){
				//insert to table gps_not_update_one_day
				$data['category'] 	 = 'Three Days';
				$data['last_update'] = $data['updated_at'];
				if(!empty($data) && isset($data['_id'])){
					unset($data['_id']);
					unset($data['updated_at']);
					unset($data['created_at']);
				} 
				MongoGpsNotUpdateOneDay::create($data);
				echo $n++."\n";
			}
		} catch(\Exception $e) {
            print_r($e->getMessage());
		}
  	}
}