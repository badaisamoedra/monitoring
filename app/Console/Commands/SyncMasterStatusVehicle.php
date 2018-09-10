<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoMasterStatusVehicle;
use App\Models\MsStatusVehicle;

class SyncMasterStatusVehicle extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'master_status_vehicle:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync Data from MySql to MongoDB';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */

	public function __construct(GlobalCrudRepo $globalCrudRepo)
	{
		parent::__construct();
		$this->globalCrudRepo = $globalCrudRepo;
		$this->globalCrudRepo->setModel(new MongoMasterStatusVehicle());
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try{
			$getStatusVehicle = MsStatusVehicle::get();

			foreach ($getStatusVehicle as $data) {
				$checkDataMongo = MongoMasterStatusVehicle::where('status_vehicle_code', $data->status_vehicle_code)->first();

				if (empty($checkDataMongo)) {
					$dataSave = [
						'status_vehicle_code'		=> $data->status_vehicle_code,
						'status_vehicle_name'		=> $data->status_vehicle_name,
						'color_hex'					=> $data->color_hex,
					];
					$data = $this->globalCrudRepo->create($dataSave);
				} else {
					$deleteByObjectId = $this->globalCrudRepo->delete('_id', $checkDataMongo->_id);
					$dataSave = [
						'status_vehicle_code'		=> $data->status_vehicle_code,
						'status_vehicle_name'		=> $data->status_vehicle_name,
						'color_hex'					=> $data->color_hex,
					];
					$data = $this->globalCrudRepo->create($dataSave);
				}
			}
			
		} catch(\Exception $e) {
            return $e->getMessage();
		}
		
		
  	}
}