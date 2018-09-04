<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsModelVehicle;
use Auth;

class VehicleModelController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsModelVehicle());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'model_vehicle_name' => 'required',
            'brand_vehicle_code' => 'required',
            'fuel_ratio' => 'required',
            'status'     => 'required'
            
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'model_vehicle_code' => $this->generateID('MDL-', $lastId, 4),
            'model_vehicle_name' => $request->model_vehicle_name,
            'brand_vehicle_code' => $request->brand_vehicle_code,
            'fuel_ratio' => $request->fuel_ratio,
            'status'     => $request->status
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('model_vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'model_vehicle_name' => 'sometimes|required',
            'brand_vehicle_code' => 'sometimes|required',
            'fuel_ratio' => 'sometimes|required',
            'status'     => 'sometimes|required',
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('model_vehicle_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('model_vehicle_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}