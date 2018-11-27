<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MaintenanceVehicle;
use App\Models\MsVehicle;
use App\Models\MwMapping;
use App\RestCurl;
use Auth;
use DB;

class VehicleMaintenanceController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MaintenanceVehicle());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        
        $this->validate($request, [
            'imei_obd_number_old' => 'required',
            'simcard_number_old'  => 'required',
            'start_date_maintenance' => 'required',
            'end_date_maintenance' => 'required',
            'descriptions' => 'required',
            'status' => 'required',
        ]);
        try{
            $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
            $input  = [
                'maintenance_vehicle_code' => $this->generateID('MVL-', $lastId, 4),
                'imei_obd_number_old' => $request->imei_obd_number_old,
                'imei_obd_number_new' => $request->imei_obd_number_new,
                'simcard_number_old'  => $request->simcard_number_old,
                'simcard_number_new'  => $request->simcard_number_new,
                'start_date_maintenance' => $request->start_date_maintenance,
                'end_date_maintenance' => $request->end_date_maintenance,
                'descriptions' => $request->descriptions,
                'status' => $request->status,
            ];

            $this->checkEmaiOrSimCard($request);
            // update imei or simcard in msvehicel
            $param = [];
            if($request->has('simcard_number_new') && !empty($request->simcard_number_new))
                $param['simcard_number'] = $request->simcard_number_new;

            if($request->has('imei_obd_number_new') && !empty($request->imei_obd_number_new))
                $param['imei_obd_number'] = $request->imei_obd_number_new;

            DB::beginTransaction();
            if(!empty($param)){
                $vehicle = MsVehicle::where('imei_obd_number', $request->imei_obd_number_old)->first();
                if(!empty($vehicle)){
                    MsVehicle::where('vehicle_code', $vehicle->vehicle_code)->update($param);
                    //delete mwmapping
                    if(isset($param['imei_obd_number']) && !empty($param['imei_obd_number'])){
                        MwMapping::where('imei', $request->imei_obd_number_old)->delete();
                        $obj = [
                            'code' => $request->imei_obd_number,
                            "description" => $vehicle->license_plate,
                            "vehicle_number" => $vehicle->chassis_number,
                            "device_type_id" => "29a94080-ee75-11e8-8c27-8553827c1ba1",
                            "device_model_id" => "29afc7f0-ee75-11e8-a736-531385a7ec2d",
                            "device_group_id" => "2c6fb400-ee75-11e8-8dbe-a5bced5f86bc"
                        ];
                        $updateParse = RestCurl::put(URL_PARSE.'/api/v1/backend/devices/'.$vehicle->reff_vehicle_id, $obj);
                        if($updateParse['status'] != '200') throw new \Exception('Failed update imei in middleware server.');
                    }

                    
                }

            }

            $new = $this->globalCrudRepo->create($input);
            DB::commit();
            return $this->makeResponse(200, 1, null, $new);
        }catch(\Exception $e){
            DB::rollback();
            return $this->makeResponse(500, 0, $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('maintenance_vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'imei_obd_number_old' => 'sometimes|required',
            'imei_obd_number_new' => 'sometimes|required',
            'simcard_number_old'  => 'sometimes|required',
            'simcard_number_new'  => 'sometimes|required',
            'start_date_maintenance' => 'sometimes|required',
            'end_date_maintenance' => 'sometimes|required',
            'descriptions' => 'sometimes|required',
            'status' => 'sometimes|required',
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('maintenance_vehicle_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    private function checkEmaiOrSimCard($request){
        if($request->has('imei_obd_number_new') && !empty($request->imei_obd_number_new)){
            //check imei
            $check = MsVehicle::where('imei_obd_number', $request->imei_obd_number_new)->first();
            if(!empty($check))  throw new \Exception('Imei already exist, can not duplicate in master.');
        }

        if($request->has('simcard_number_new') && !empty($request->simcard_number_new)){
            //check simcard
            $check = MsVehicle::where('simcard_number', $request->simcard_number_new)->first();
            if(!empty($check)) throw new \Exception('Simcard already exist, can not duplicate in master.');
        }
        return true;
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('maintenance_vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}