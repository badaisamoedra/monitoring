<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsZone;
use Auth;

class ZoneController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsZone());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all(['zone_detail']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'type_zone'     => 'required',
            'zone_name'     => 'required',
            'status'        => 'required',
            'area_code'     => 'required',
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'zone_code' => $this->generateID('MSZ-', $lastId, 4),
            'type_zone' => $request->type_zone,
            'zone_name' => $request->zone_name,
            'status'    => $request->status,
            'area_code'    => $request->area_code,
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('zone_code', $id, ['zone_detail']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'type_zone'     => 'sometimes|required',
            'zone_name'     => 'sometimes|required',
            'status'        => 'sometimes|required',
            'area_code'    => 'sometimes|required',
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('zone_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('zone_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}