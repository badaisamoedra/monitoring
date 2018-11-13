<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MwMappingHistory;
use App\Models\MongoMasterAddress;

class SyncAddress extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'sync_address:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync from Master Address to address in mapping history';

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
			
			$getHistory  = MwMappingHistory::where('last_location', null)->get()->toArray();
			$n = 1;
            if(!empty($getHistory)) foreach($getHistory as $hsty){
                sleep(2);
                $longlat =  $hsty['longitude'].$hsty['latitude'];
                $checkFromMasterAddress = MongoMasterAddress::where("longlat",  $longlat)->first();
                if(!empty($checkFromMasterAddress)){
                    $address = $checkFromMasterAddress->address;
                }else{
                    //Send request and receive json data by address
                    $geocodeFromLatLong = file_get_contents('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.trim($hsty['latitude']).'&lon='.trim($hsty['longitude']).'&limit=1&email=badai.samoedra@gmail.com'); 
                    $output = json_decode($geocodeFromLatLong);
                    //Get lat, long and address from json data
                    $address   = !empty($output) ? $output->display_name:'';
                    //store to master address
                    MongoMasterAddress::create([
                                            'latitude'  => $hsty['latitude'],
                                            'longitude' => $hsty['longitude'],
                                            'address'   => $address,
                                            'longlat'   => $longlat 
                                        ]);
                }
                //update mw_mapping_history
                $history = MwMappingHistory::where('_id', $hsty['_id'])->first();
                if(!empty($history)){
                    $history->last_location = $address;
                    $history->save();
				}
				echo $n."\n";
				$n++;
			}
			echo 'Success';
		} catch(\Exception $e) {
            print_r($e->getMessage());
		}
  	}
}