<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoMasterEventRelated;
use App\Models\MsAlert;
use App\Models\MsNotification;
use App\Models\MsStatusAlertPriority;

class SyncMasterEventRelated extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'master_event_related:sync';

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
		$this->globalCrudRepo->setModel(new MongoMasterEventRelated());
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try{
			$getEvent = MsAlert::with(['notification', 'alertPriority'])->get();

			foreach ($getEvent as $data) {

				$checkDataMongo = MongoMasterEventRelated::where('alert_code', $data->alert_code)->first();
				$deleteByObjectId = $this->globalCrudRepo->delete('_id', $checkDataMongo->_id);

				if($deleteByObjectId) {
					$getNotif = MsNotification::where('notification_code', $data->notification_code)->first();
					$dataNotif = !empty($getNotif) ? $getNotif->toArray() : NULL;

					$getPriority = MsStatusAlertPriority::where('alert_priority_code', $data->status_alert_priority_code)->first();
					$dataPriority = !empty($getPriority) ? $getPriority->toArray() : NULL;

					$dataSave = [
						'alert_code' 					=> $data->alert_code,
						'alert_name' 					=> $data->alert_name,
						'notification_code' 			=> $data->notification_code,
						'provision_alert_name' 			=> $data->provision_alert_name,
						'provision_alert_code' 			=> $data->provision_alert_code,
						'score' 						=> $data->score,
						'status_alert_priority_code' 	=> $data->status_alert_priority_code,
						'notif_detail'					=> $dataNotif,
						'priority_detail'				=> $dataPriority,
					];

					$data = $this->globalCrudRepo->create($dataSave);
				}

			}

		} catch(\Exception $e) {
            return $e->getMessage();
		}
		
		
  	}
}