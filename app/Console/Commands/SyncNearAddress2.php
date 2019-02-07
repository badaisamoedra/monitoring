<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\TestAddress;
use App\Models\MongoLogsFillAddress;
use App\Models\MongoMasterAddress;


class SyncNearAddress2 extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'sync_near_address_2:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'sync near address at 12.00';

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
		date_default_timezone_set('Asia/Jakarta');
		$start_time = date('Y-m-d H:i:s');
        $time_start = microtime(true); 
		try{
			ini_set("memory_limit",-1);
			ini_set('max_execution_time', 0);

			$tempLonglat = [];
			$needToGetFromOpenStreet = [];
			$data = \DB::connection('mongodb')->collection('mw_mapping_history')
											  ->timeout(-1)
                                              ->whereNull('last_location')
                                              ->take(200000)
                                              ->get();
						  
			if(!empty($data)){
				foreach($data as $val){
					if(in_array($val['longlat'], $tempLonglat)) continue;
					$getRadius = \DB::connection('mongodb')->collection('mw_mapping_history')
								    ->timeout(-1)
									->select('longlat')->distinct('longlat')
									->where('location_coordinate', 'near', [
														'$geometry' => [
															'type' => 'Point',
															'coordinates' => [
																$val['longitude'],
																$val['latitude']
															],
														],
														'$maxDistance' => 1500,
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
									->timeout(-1)
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
					$geocodeFromLatLong = file_get_contents('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.trim($val['latitude']).'&lon='.trim($val['longitude']).'&limit=1&email=fejaena.6@gmail.com'); 
					$output    = json_decode($geocodeFromLatLong);
					$address   = $this->formatAddress($output);
					
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
				'file_function'  => 'New Sync Address 2 (Start = '. $start_time.' End = '.date('Y-m-d H:i:s').')',
				'execution_time' => microtime(true) - $time_start,
				'Message' 		 => 'Success sync data address '.date('Y-m-d H:i:s')
			];
			$logs = MongoLogsFillAddress::create($saveLogs);
			echo 'success';
		} catch(\Exception $e) {
			$saveLogs = [
				'status' 		 => 'ERROR',
				'file_function'  => 'New Sync Address 2 (Start = '. $start_time.' End = '.date('Y-m-d H:i:s').')',
				'execution_time' => microtime(true) - $time_start,
				'Message' 		 => $e->getMessage().' '.date('Y-m-d H:i:s')
			];
			$logs = MongoLogsFillAddress::create($saveLogs);
            echo 'error';
		}
		
		
	}

	private function formatAddress($output){
		if(!empty($output)){
			$village        = isset($output->address->village) ? $output->address->village.', ' : '';
			$county         = isset($output->county) ? $output->county.', ' : '';
			$state_district = isset($output->address->state_district) ? $output->address->state_district.', ' : '';
			$state          = isset($output->address->state) ? $output->address->state.', ' : '';
			$country        = isset($output->address->country) ? $output->address->country.'.' : '';
			if(empty($village) && !empty($county)){
				$village = $county;
			}
			if(empty($state) && !empty($state_district)){
				$state = $state_district;
			}
			$address   = $village.$state.$country;
		}else{
			$address   = null;
		}
		return $address;
	}
	  
}