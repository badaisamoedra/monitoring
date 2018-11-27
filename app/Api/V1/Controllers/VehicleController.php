<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsVehicle;
use Auth;
use App\Helpers;
use App\RestCurl;
use DB;

class VehicleController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsVehicle());
    }

    public function index(Request $request)
    {
        $license_plate = $request->has('license_plate') ? $request->license_plate : '';
        if(!empty($license_plate))
            $data = $this->globalCrudRepo->search('license_plate', $request->query('license_plate'), ['brand','model']);
        else
            $data = $this->globalCrudRepo->all(['brand','model']);
        
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        try{
            $this->validate($request, [
                'license_plate' => 'required',
                'imei_obd_number' => 'required',
                'simcard_number' => 'required',
                'year_of_vehicle' => 'required',
                'color_vehicle' => 'required',
                'brand_vehicle_code' => 'required',
                'model_vehicle_code' => 'required',
                'chassis_number' => 'required',
                'machine_number' => 'required',
                'date_stnk' => 'required',
                'date_installation' => 'required',
                'speed_limit' => 'required',
                'odometer' => 'required',
                'area_code' => 'required',
                'status'=> 'required',
            ]);

            DB::beginTransaction();
            $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
            $input  = [
                'vehicle_code' => $this->generateID('UNT-', $lastId, 6),
                'license_plate' => $request->license_plate,
                'imei_obd_number' => $request->imei_obd_number,
                'simcard_number' => $request->simcard_number,
                'year_of_vehicle' => $request->year_of_vehicle,
                'color_vehicle' => $request->color_vehicle,
                'brand_vehicle_code' => $request->brand_vehicle_code,
                'model_vehicle_code' => $request->model_vehicle_code,
                'chassis_number' => $request->chassis_number,
                'machine_number' => $request->machine_number,
                'date_stnk' => $request->date_stnk,
                'date_installation' => $request->date_installation,
                'speed_limit' => $request->speed_limit,
                'odometer' => $request->odometer,
                'area_code' => $request->area_code,
                'status'=> $request->status,
            ];
            

            $data = [
                "code" => $request->imei_obd_number,
                "description" => $request->license_plate,
                "vehicle_number" => $request->chassis_number,
                "device_type_id" => "29a94080-ee75-11e8-8c27-8553827c1ba1",
                "device_model_id" => "29afc7f0-ee75-11e8-a736-531385a7ec2d",
                "device_group_id" => "2c6fb400-ee75-11e8-8dbe-a5bced5f86bc"
            ];
            $createParse = RestCurl::post(URL_PARSE.'/api/v1/backend/devices', $data);
            if($createParse['status'] == 201){
                $input['reff_vehicle_id'] =  $createParse['data']->data->id;
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
        $license_plate =  str_replace("%20"," ",$id);
        $data = $this->globalCrudRepo->find('license_plate', $license_plate);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'license_plate' => 'sometimes|required',
            'imei_obd_number' => 'sometimes|required',
            'simcard_number' => 'sometimes|required',
            'year_of_vehicle' => 'sometimes|required',
            'color_vehicle' => 'sometimes|required',
            'brand_vehicle_code' => 'sometimes|required',
            'model_vehicle_code' => 'sometimes|required',
            'chassis_number' => 'sometimes|required',
            'machine_number' => 'sometimes|required',
            'date_stnk' => 'sometimes|required',
            'date_installation' => 'sometimes|required',
            'speed_limit' => 'sometimes|required',
            'odometer' => 'sometimes|required',
            'area_code' => 'sometimes|required',
            'status'=> 'sometimes|required',
        ]);
        try{
            DB::beginTransaction();
            $input = $request->except(['token']);
            $existingVehicle = $this->globalCrudRepo->find('license_plate', $request->license_plate);
            if(!empty($existingVehicle) && ($existingVehicle->imei_obd_number != $request->imei_obd_number)){
                $obj = [
                        'code' => $request->imei_obd_number,
                        "description" => $existingVehicle->license_plate,
                        "vehicle_number" => $existingVehicle->chassis_number,
                        "device_type_id" => "29a94080-ee75-11e8-8c27-8553827c1ba1",
                        "device_model_id" => "29afc7f0-ee75-11e8-a736-531385a7ec2d",
                        "device_group_id" => "2c6fb400-ee75-11e8-8dbe-a5bced5f86bc"
                    ];
                $updateParse = RestCurl::put(URL_PARSE.'/api/v1/backend/devices/'.$existingVehicle->reff_vehicle_id, $obj);
                if($updateParse['status'] != '200') throw new \Exception('Failed update imei in middleware server.');
            }
            $update = $this->globalCrudRepo->update('vehicle_code', $id, $input);

            // Update Sync Vehicle
            Helpers::updateToSync(null, $id);
            DB::commit();

            return $this->makeResponse(200, 1, null, $update);
        }catch(\Exception $e){
            DB::rollback();
            return $this->makeResponse(500, 0, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }

}