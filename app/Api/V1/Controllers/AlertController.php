<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\MsAlert;
use Auth;
use DB;

class AlertController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new MsAlert());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all(['notification']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'alert_name' => 'required',
            'notification_code' => 'required',
            'provision_alert_name' => 'required',
            'provision_alert_code' => 'required',
            'score' => 'required',
            'status_alert_priority_code' => 'required'
            
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'alert_code' => $this->generateID('MRT-', $lastId, 4),
            'alert_name' => $request->alert_name,
            'notification_code' => $request->notification_code,
            'provision_alert_name' => $request->provision_alert_name,
            'provision_alert_code' => $request->provision_alert_code,
            'score' => $request->score,
            'status_alert_priority_code' => $request->status_alert_priority_code
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('alert_code', $id, ['notification']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'alert_name' => 'sometimes|required',
            'notification_code' => 'sometimes|required',
            'provision_alert_name' => 'sometimes|required',
            'provision_alert_code' => 'sometimes|required',
            'score' => 'sometimes|required',
            'status_alert_priority_code' => 'sometimes|required'
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('alert_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('alert_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}