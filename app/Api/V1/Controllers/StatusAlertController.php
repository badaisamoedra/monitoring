<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsStatusAlert;
use Auth;

class StatusAlertController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsStatusAlert());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'status_alert_name' => 'required',
            'status_alert_color_hex' => 'required',
            'status' => 'required'
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'status_alert_code' => $this->generateID('SRT-', $lastId, 4),
            'status_alert_name' => $request->status_alert_name,
            'status_alert_color_hex' => $request->status_alert_color_hex,
            'status' => $request->status
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('status_alert_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'status_alert_name' => 'sometimes|required',
            'status_alert_color_hex' => 'sometimes|required',
            'status' => 'sometimes|required',
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('status_alert_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('status_alert_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}