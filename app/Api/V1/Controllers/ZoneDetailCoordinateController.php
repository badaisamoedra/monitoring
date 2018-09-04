<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsZoneDetailCoordinate;
use Auth;

class ZoneDetailCoordinateController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsZoneDetailCoordinate());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'zone_code'   => 'required',
            'latitude'  => 'required',
            'longitude' => 'required',
            'status'    => 'required',
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'zone_detail_coordinate_code' => $this->generateID('MZC-', $lastId, 4),
            'zone_code'   => $request->zone_code,
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'status'    => $request->status,
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('zone_detail_coordinate_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'zone_code'   => 'sometimes|required',
            'latitude'  => 'sometimes|required',
            'longitude' => 'sometimes|required',
            'status'    => 'sometimes|required',
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('zone_detail_coordinate_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('zone_detail_coordinate_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}