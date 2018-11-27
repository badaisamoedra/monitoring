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
use Maatwebsite\Excel\Facades\Excel;

class ProvisionBulk extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsVehicle());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all(['brand','model']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        try{
            $fileName = $request->fileName;
            $new = [];
            $path  = base_path().'/public/'.$fileName;
            $datas = Excel::load( $path , function($reader) {})->get();

            foreach($datas[0] as $data){
                $checkData = $this->globalCrudRepo->find('imei_obd_number', $data->imei);
                $checkData2 = $this->globalCrudRepo->find('machine_number', $data->no_mesin);
                $checkData3 = $this->globalCrudRepo->find('simcard_number', $data->no_simcard);
                if (!empty($checkData)) {
                    continue;
                }
                if (!empty($checkData2)) {
                    continue;
                }
                if (!empty($checkData3)) {
                    continue;
                }
                DB::beginTransaction();
                $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
                $input  = [
                    'vehicle_code' => $this->generateID('UNT-', $lastId, 6),
                    'license_plate' => $data->nopol,
                    'imei_obd_number' => $data->imei,
                    'simcard_number' => $data->no_simcard,
                    'year_of_vehicle' => $data->year,
                    'color_vehicle' => $data->color,
                    'brand_vehicle_code' => $data->brand_vehicle_code,
                    'model_vehicle_code' => $data->model_vehicle_code,
                    'chassis_number' => $data->no_rangka,
                    'machine_number' => $data->no_mesin,
                    'date_stnk' => NULL,
                    'date_installation' => NULL,
                    'speed_limit' => NULL,
                    'odometer' => $data->odometer,
                    'area_code' => $data->area_code,
                    'status'=> 1,
                    'reff_vehicle_id' => $data->reff_id,
                ];

                /*
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
                */

                $new = $this->globalCrudRepo->create($input);
                DB::commit();
            }
            return $this->makeResponse(200, 1, null, $new);
        }catch(\Exception $e){
            DB::rollback();
            return $this->makeResponse(500, 0, $e->getMessage());
        }
    }

}