<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use App\Models\TransactionVehiclePair;
use Auth;

class TransactionVehiclePairController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new TransactionVehiclePair());
    }

    public function index()
    {
        $data = $this->globalCrudRepo->all(['vehicle', 'driver']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'vehicle_code' => 'required',
            'driver_code'  => 'required',
            'start_date_pair' => 'required',
            'end_date_pair' => 'required',
            'status' => 'required',
        ]);
        $lastId = $this->globalCrudRepo->last() ? $this->globalCrudRepo->last()->id : 0;
        $input  = [
            'transaction_vehicle_pair_code' => $this->generateID('TVP-', $lastId, 6),
            'vehicle_code' => $request->vehicle_code,
            'driver_code'  => $request->driver_code,
            'start_date_pair' => $request->start_date_pair,
            'end_date_pair' => $request->end_date_pair,
            'status' => $request->status,
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('transaction_vehicle_pair_code', $id, ['vehicle', 'driver']);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'vehicle_code' => 'sometimes|required',
            'driver_code'  => 'sometimes|required',
            'start_date_pair' => 'sometimes|required',
            'end_date_pair' => 'sometimes|required',
            'status' => 'sometimes|required',
        ]);
        $input = $request->except(['token']);
        $update = $this->globalCrudRepo->update('transaction_vehicle_pair_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('transaction_vehicle_pair_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}