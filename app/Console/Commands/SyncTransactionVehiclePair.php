<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoTrxVehiclePair;
use App\Models\TransactionVehiclePair;
use DB;

class SyncTransactionVehiclePair extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'trx_vehicle_pair:sync';

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
		$this->globalCrudRepo->setModel(new MongoTrxVehiclePair());
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try{
			$vehiclePair = TransactionVehiclePair::all();

			if(empty($vehiclePair)){
                throw new \Exception("Error Processing Request. Data is Empty");	
			}

			
			

		} catch(\Exception $e) {
            return $this->makeResponse(500, 0, $e->getMessage(), null);
		}
		
		
  	}
}