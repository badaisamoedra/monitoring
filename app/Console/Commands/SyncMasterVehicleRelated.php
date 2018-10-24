<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MongoMasterVehicleRelated;
use App\Models\MongoLogsSync;
use App\Models\TransactionVehiclePair;
use App\Models\MongoLogsIntegration;
use App\Models\MsVehicle;
use App\Models\MsZone;

class SyncMasterVehicleRelated extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'master_vehicle_related:sync';

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
		$this->globalCrudRepo->setModel(new MongoMasterVehicleRelated());
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try{
			$start = microtime(true);
			$vehiclePair = TransactionVehiclePair::with(['vehicle', 'driver'])->get();
			
			foreach ($vehiclePair as $data) {
				$checkDataMongo = MongoMasterVehicleRelated::where('vehicle_code', $data->vehicle->vehicle_code)->first();

				if (empty($checkDataMongo)) {
					$vehicleBrandModel = MsVehicle::with(['brand','model'])->where('vehicle_code', $data->vehicle->vehicle_code)->first();
					$dataBrandModel = $vehicleBrandModel->toArray();

					$getZones = MsZone::with(['zone_detail'])->where('area_code', $data->vehicle->area_code)->get();
					$dataZones = $getZones->toArray();

					$vehicle = [
						'vehicle_code'			=> $data->vehicle->vehicle_code,
						'license_plate'			=> $data->vehicle->license_plate,
						'imei_obd_number'		=> $data->vehicle->imei_obd_number,
						'simcard_number'		=> $data->vehicle->simcard_number,
						'year_of_vehicle'		=> $data->vehicle->year_of_vehicle,
						'color_vehicle'			=> $data->vehicle->color_vehicle,
						'brand_vehicle_code'	=> $data->vehicle->brand_vehicle_code,
						'model_vehicle_code'	=> $data->vehicle->model_vehicle_code,
						'chassis_number'		=> $data->vehicle->chassis_number,
						'machine_number'		=> $data->vehicle->machine_number,
						'date_stnk'				=> $data->vehicle->date_stnk,
						'date_installation'		=> $data->vehicle->date_installation,
						'speed_limit'			=> $data->vehicle->speed_limit,
						'odometer'				=> $data->vehicle->odometer,
						'area_code'				=> $data->vehicle->area_code,
						'status'				=> $data->vehicle->status,
						'updated_at'			=> $data->vehicle->updated_at,
						'created_at'			=> $data->vehicle->created_at,
						'deleted_at'			=> $data->vehicle->deleted_at,
						'brand'					=> $dataBrandModel['brand'],
						'model'					=> $dataBrandModel['model'],
						'area'					=> $dataBrandModel['area'],
						'zone'					=> $dataZones
					];

					$driver = [
						'driver_code'	=> $data->driver->driver_code,
						'name'			=> $data->driver->name,
						'spk_number'	=> $data->driver->spk_number,
						'area_code'		=> $data->driver->area_code,
						'status'		=> $data->driver->status,
						'created_at'	=> $data->driver->created_at,
						'updated_at'	=> $data->driver->updated_at,
						'deleted_at'	=> $data->driver->deleted_at
					];

					$dataPair = [
						'transaction_vehicle_pair_code' => $data->transaction_vehicle_pair_code,
						'vehicle_code'					=> $data->vehicle_code,
						'driver_code'					=> $data->driver_code,
						'start_date_pair'				=> $data->start_date_pair,
						'end_date_pair'					=> $data->end_date_pair,
						'status'						=> $data->status,
						'vehicle'						=> $vehicle,
						'driver'						=> $driver
					];

					$data = $this->globalCrudRepo->create($dataPair);
					
				} else {

					$deleteByObjectId = $this->globalCrudRepo->delete('_id', $checkDataMongo->_id);
					$vehicleBrandModel = MsVehicle::with(['brand','model','area'])->where('vehicle_code', $data->vehicle->vehicle_code)->first();
					$dataBrandModel = $vehicleBrandModel->toArray();

					$getZones = MsZone::with(['zone_detail'])->where('area_code', $data->vehicle->area_code)->get();
					$dataZones = $getZones->toArray();

					$vehicle = [
						'vehicle_code'			=> $data->vehicle->vehicle_code,
						'license_plate'			=> $data->vehicle->license_plate,
						'imei_obd_number'		=> $data->vehicle->imei_obd_number,
						'simcard_number'		=> $data->vehicle->simcard_number,
						'year_of_vehicle'		=> $data->vehicle->year_of_vehicle,
						'color_vehicle'			=> $data->vehicle->color_vehicle,
						'brand_vehicle_code'	=> $data->vehicle->brand_vehicle_code,
						'model_vehicle_code'	=> $data->vehicle->model_vehicle_code,
						'chassis_number'		=> $data->vehicle->chassis_number,
						'machine_number'		=> $data->vehicle->machine_number,
						'date_stnk'				=> $data->vehicle->date_stnk,
						'date_installation'		=> $data->vehicle->date_installation,
						'speed_limit'			=> $data->vehicle->speed_limit,
						'odometer'				=> $data->vehicle->odometer,
						'area_code'				=> $data->vehicle->area_code,
						'status'				=> $data->vehicle->status,
						'updated_at'			=> $data->vehicle->updated_at,
						'created_at'			=> $data->vehicle->created_at,
						'deleted_at'			=> $data->vehicle->deleted_at,
						'brand'					=> $dataBrandModel['brand'],
						'model'					=> $dataBrandModel['model'],
						'area'					=> $dataBrandModel['area'],
						'zone'					=> $dataZones
					];

					$driver = [
						'driver_code'	=> $data->driver->driver_code,
						'name'			=> $data->driver->name,
						'spk_number'	=> $data->driver->spk_number,
						'area_code'		=> $data->driver->area_code,
						'status'		=> $data->driver->status,
						'created_at'	=> $data->driver->created_at,
						'updated_at'	=> $data->driver->updated_at,
						'deleted_at'	=> $data->driver->deleted_at
					];
					
					$dataPair = [
						'transaction_vehicle_pair_code' => $data->transaction_vehicle_pair_code,
						'vehicle_code'					=> $data->vehicle_code,
						'driver_code'					=> $data->driver_code,
						'start_date_pair'				=> $data->start_date_pair,
						'end_date_pair'					=> $data->end_date_pair,
						'status'						=> $data->status,
						'vehicle'						=> $vehicle,
						'driver'						=> $driver
					];

					$data = $this->globalCrudRepo->create($dataPair);
				}
			}

			$time_elapsed_secs = microtime(true) - $start;

			$saveLogs = [
				'status' => 'SUCCESS',
				'file_function' => 'SyncMasterVehicleRelated',
				'execution_time' => $time_elapsed_secs,
				'Message' => ''
			];
			$logs = MongoLogsSync::create($saveLogs);

		} catch(\Exception $e) {
			$saveLogs = [
				'status' => 'ERROR',
				'file_function' => 'SyncMasterVehicleRelated',
				'execution_time' => '0',
				'Message' => $e->getMessage()
			];
			$logs = MongoLogsSync::create($saveLogs);
            return $logs;
		}
		
		
  	}
}