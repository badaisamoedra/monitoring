<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsVehicle;
use Auth;

class VehicleController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsVehicle());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
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
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'vehicle_code' => $this->generateID('UNT-', $lastId, 4),
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
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('vehicle_code', $id);
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
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('vehicle_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}