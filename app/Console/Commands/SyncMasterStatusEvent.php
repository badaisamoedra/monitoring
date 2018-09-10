<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoMasterStatusEvent;
use App\Models\MsStatusAlert;

class SyncMasterStatusEvent extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'master_status_event:sync';

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
		$this->globalCrudRepo->setModel(new MongoMasterStatusEvent());
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try{
			$getStatusEvent = MsStatusAlert::get();

			foreach($getStatusEvent as $data) {
				$checkDataMongo = MongoMasterStatusEvent::where('status_alert_code', $data->status_alert_code)->first();
				if (empty($checkDataMongo)) {
					$dataSave = [
						'status_alert_code'			=> $data->status_alert_code,
						'status_alert_name'			=> $data->status_alert_name,
						'status_alert_color_hex'	=> $data->status_alert_color_hex,
						'status'					=> $data->status
					];
					$data = $this->globalCrudRepo->create($dataSave);
				} else {
					$deleteByObjectId = $this->globalCrudRepo->delete('_id', $checkDataMongo->_id);
					$dataSave = [
						'status_alert_code'			=> $data->status_alert_code,
						'status_alert_name'			=> $data->status_alert_name,
						'status_alert_color_hex'	=> $data->status_alert_color_hex,
						'status'					=> $data->status
					];
					$data = $this->globalCrudRepo->create($dataSave);
				}
			}
			

		} catch(\Exception $e) {
            return $e->getMessage();
		}
		
		
  	}
}