<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsStatusAlertPriority;
use Auth;

class StatusAlertPriorityController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsStatusAlertPriority());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all();
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'alert_priority_name' => 'required',
            'alert_priority_color_hex' => 'required'
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'alert_priority_code' => $this->generateID('STP-', $lastId, 4),
            'alert_priority_name' => $request->alert_priority_name,
            'alert_priority_color_hex' => $request->alert_priority_color_hex
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('alert_priority_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'alert_priority_name' => 'sometimes|required',
            'alert_priority_color_hex' => 'sometimes|required'
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('alert_priority_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('alert_priority_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}