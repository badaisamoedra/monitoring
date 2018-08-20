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
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'vehicle_code' => $this->generateID('VHC-', $lastId, 4),
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
        $input = $request->all();
        $update = $this->globalCrudRepo->update('vehicle_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}