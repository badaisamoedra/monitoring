<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\TestAddress;
use App\Models\MongoLogsSync;
use App\Models\MongoMasterAddress;


class TestNearAddress extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'test_address:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'test address';

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
			$time_start = microtime(true); 

			$tempLonglat = [];
			$needToGetFromOpenStreet = [];
			$data = \DB::connection('mongodb')->collection('mw_mapping_history')
											  ->whereNull('last_location')
											  ->get();
											  
			if(!empty($data)){
				foreach($data as $val){
					if(in_array($val['longlat'], $tempLonglat)) continue;
					$getRadius = \DB::connection('mongodb')->collection('mw_mapping_history')
									->select('longlat')->distinct('longlat')
									->where('location_coordinate', 'near', [
														'$geometry' => [
															'type' => 'Point',
															'coordinates' => [
																$val['longitude'],
																$val['latitude']
															],
														],
														'$maxDistance' => 1000,
								])->get();
																
					if(!empty($getRadius)){
						$getRadius = $getRadius->toArray();
						array_push($getRadius, $val['longlat']);

						$tempLonglat = array_merge($tempLonglat, $getRadius);
						$tempLonglat = array_unique($tempLonglat);

						$checkFromMasterAddress = MongoMasterAddress::where("longlat",  $val['longlat'])->first();
						if(!empty($checkFromMasterAddress) && !empty($checkFromMasterAddress->address)){
							$address = $checkFromMasterAddress->address;
							// update address
							\DB::connection('mongodb')
									->collection('mw_mapping_history')
									->whereIn('longlat', $getRadius)
									->update(['last_location' => $address]);
						}else{
							$needToGetFromOpenStreet[] = [
								"longlat"   => $val['longlat'],
								"latitude"  => $val['latitude'],
								"longitude" => $val['longitude'],
								"detail"    => $getRadius
							];
						}
						
					}
				}
			}

			$tempLonglat = [];
			if(!empty($needToGetFromOpenStreet)){
				foreach($needToGetFromOpenStreet as $val){
					if(in_array($val['longlat'], $tempLonglat)) continue;

					sleep(2);
					//Send request and receive json data by address
					$geocodeFromLatLong = file_get_contents('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.trim($val['latitude']).'&lon='.trim($val['longitude']).'&limit=1&email=fejaena.2@gmail.com'); 
					$output    = json_decode($geocodeFromLatLong);
					$address   = !empty($output) ? $output->display_name: null;
					
					$tempLonglat = array_merge($tempLonglat, $val['detail']);
					$tempLonglat = array_unique($tempLonglat);
					// update address
					\DB::connection('mongodb')
							->collection('mw_mapping_history')
							->whereIn('longlat',  $val['detail'])
							->update(['last_location' => $address]);
				}
			}
			$saveLogs = [
				'status' 		 => 'SUCCESS',
				'file_function'  => 'New Sync Address',
				'execution_time' => microtime(true) - $time_start,
				'Message' 		 => 'Success sync data address'
			];
			$logs = MongoLogsSync::create($saveLogs);
			echo 'success';
		} catch(\Exception $e) {
			$saveLogs = [
				'status' 		 => 'ERROR',
				'file_function'  => 'New Sync Address',
				'execution_time' => microtime(true) - $time_start,
				'Message' 		 => $e->getMessage()
			];
			$logs = MongoLogsSync::create($saveLogs);
            return $logs;
		}
		
		
  	}
}