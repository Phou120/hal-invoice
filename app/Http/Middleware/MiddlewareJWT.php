<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class MiddlewareJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {                   // Exception is

            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'message' => 'Token ບໍ່ຖືກຕ້ອງ ກະລຸນາກວດສອບກ່ອນ...'
                ], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'message' => 'Token ໝົດອາຍຸການໃຊ້ງານ...'
                ], 401);
            } else if ($e instanceof TokenBlacklistedException) {
                return response()->json([
                    'message' => 'Token is in black list'
                ], 401);
            } else {
                return response()->json(['message' => 'ບໍ່ພົບ token'], 401);
            }
        }
        return $next($request);
    }
}
