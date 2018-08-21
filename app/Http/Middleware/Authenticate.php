<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (! $token = $this->auth->setRequest($request)->getToken()) {
            return response()->json(['Status' => 0, 'Code' => 400,  'Data' => '', 'ErrorMessage' => 'Token is not provided'], 400);
        }
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return response()->json(['Status' => 0, 'Code' => 410,  'Data' => '', 'ErrorMessage' => 'JWT Token Expired'], 410);
        } catch (TokenInvalidException $e) {
            $message = $e->getMessage();
            return response()->json(['Status' => 0, 'Code' => 409,  'Data' => '', 'ErrorMessage' => $message], 409);
        } catch (JWTException $e) {
            return response()->json(['Status' => 0, 'Code' => 408,  'Data' => '', 'ErrorMessage' => 'There is a problem with JWT Token'], 408);
        }

        if (! $user) {
            return response()->json(['Status' => 0, 'Code' => 404,  'Data' => '', 'ErrorMessage' => 'User not Found'], 404);
        }

        return $next($request);
    }
}
