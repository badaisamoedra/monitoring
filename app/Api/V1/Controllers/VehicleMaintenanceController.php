<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MaintenanceVehicle;
use Auth;

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
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'maintenance_vehicle_code' => $this->generateID('VHM-', $lastId, 4),
            'imei_obd_number_old' => $request->imei_obd_number_old,
            'imei_obd_number_new' => $request->imei_obd_number_new,
            'simcard_number_old'  => $request->simcard_number_old,
            'simcard_number_new'  => $request->simcard_number_new,
            'start_date_maintenance' => $request->start_date_maintenance,
            'end_date_maintenance' => $request->end_date_maintenance,
            'status' => $request->status,
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('maintenance_vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $update = $this->globalCrudRepo->update('maintenance_vehicle_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('maintenance_vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}