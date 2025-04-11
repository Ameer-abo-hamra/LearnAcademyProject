<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Auth;

class CheckUsers
{
    use \App\Traits\ResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $guard)
    {

        if (!$request->header('Authorization')) {
            return $this->returnError("Where is the token?????", 401);
        }

        try {
            $user = Auth::guard($guard)->user();
            if (!$user) {
                return $this->returnError("you have to login before", 404);
            }


        } catch (TokenExpiredException $e) {
            return $this->returnError("Token has expired", 401);
        } catch (TokenInvalidException $e) {
            return $this->returnError("Token is invalid", 401);
        } catch (JWTException $e) {
            return $this->returnError("Token is missing or invalid", 401);
        }
        return $next($request);
    }
}
