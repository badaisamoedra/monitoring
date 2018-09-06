<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsAreas;
use Auth;

class AreasController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsAreas());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all(['role']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'area_code' => 'required',
            'area_name' => 'required',
            'status' => 'required'
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'area_code' => $this->generateID('ARA-', $lastId, 4),
            'area_name'  => $request->area_name,
            'status'     => $request->status,
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('area_code', $id, ['role']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'area_code' => 'sometimes|required',
            'area_name' => 'sometimes|required',
            'status' => 'sometimes|required'
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('area_code' ,$id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('area_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}