<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Auth;

class AuthenticateController extends BaseController
{
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        try {
            $token = Auth::attempt($credentials);
            if(!$token) {
                return $this->makeResponse(401, 0,'Email atau Password anda salah.');
            }

        } catch (JWTException $e) {
            return $this->makeResponse(500, 0, 'could_not_create_token');
        }

        $user_data = Auth::user();
        //$user_data->role = Role::where('id',$user_data->role_id)->first();

        return $this->makeResponse(200, 1, "",compact('token','user_data'));
    }

    public function getAuthUser(){
        $data = Auth::user();
        return $this->makeResponse(200, 1, "", $data);
    }
}