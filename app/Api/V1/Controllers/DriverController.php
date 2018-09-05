<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsDriver;
use Auth;

class DriverController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsDriver());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'        => 'required',
            'spk_number'  => 'required',
            'area_code'   => 'required',
            'status'      => 'required',
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'driver_code' => $this->generateID('DRV-', $lastId, 6),
            'name'        => $request->name,
            'spk_number'  => $request->spk_number,
            'area_code'   => $request->area_code,
            'status'      => $request->status,
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('driver_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'        => 'sometimes|required',
            'spk_number'  => 'sometimes|required',
            'area_code'   => 'sometimes|required',
            'status'      => 'sometimes|required',
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('driver_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('driver_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}