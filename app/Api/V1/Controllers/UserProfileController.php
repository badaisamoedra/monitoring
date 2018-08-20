<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\GlobalCrudRepo as GlobalCrudRepo;
use Illuminate\Support\Facades\Hash;
use App\User;
use Auth;


class UserProfileController extends BaseController
{
    public function __construct(GlobalCrudRepo $globalCrudRepo)
    {
        $this->globalCrudRepo = $globalCrudRepo;
        $this->globalCrudRepo->setModel(new User());
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
            'user_profile_code' => $this->generateID('USR-', $lastId, 4),
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'no_telp'    => $request->no_telp,
            'identity'   => $request->identity,
            'telegram'   => $request->telegram,
            'role_code'  => $request->role_code,
            'notification_code' => $request->notification_code,
            'status'     => $request->status,
        ];
        $new = $this->globalCrudRepo->create($input);
        return $this->makeResponse(200, 1, null, $new);
    }

    public function show(Request $request, $id)
    {
        $data = $this->globalCrudRepo->find('user_profile_code', $id);
        return $this->makeResponse(200, 1, null, $data);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $update = $this->globalCrudRepo->update('user_profile_code', $id, $input);
        return $this->makeResponse(200, 1, null, $update);
    }

    public function destroy($id)
    {
        $delete = $this->globalCrudRepo->delete('user_profile_code', $id);
        return $this->makeResponse(200, 1, null, $delete);
    }
}