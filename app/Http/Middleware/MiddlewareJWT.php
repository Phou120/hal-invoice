<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\JWT;
use Illuminate\Http\Request;
use App\Models\OauthAccessToken;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
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
        } catch (\Exception $e) {                   
            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'message' => 'token ບໍ່ຖືກຕ້ອງ ກະລຸນາກວດສອບກ່ອນ...'
                ], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'message' => 'token ໝົດອາຍຸການໃຊ້ງານ...'
                ], 401);
            } else if ($e instanceof TokenBlacklistedException) {
                return response()->json([
                    'message' => 'token is in black list'
                ], 401);
            } else {
                return response()->json(['message' => 'ບໍ່ພົບ token'], 401);
            }
        }
        
        /** Check Revoked Token */
        try {
            $this->isRevokedAccessToken();
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }

    private function isRevokedAccessToken()
    {
        $payload = auth()->payload()->toArray();
        
        if (array_key_exists('type', $payload) && $payload['type'] == 'refresh') {
            throw new Exception('token ນີ້ບໍ່ສາມາດໃຊ້ໄດ້.');
        }
        
        $existsRevokedToken = OauthAccessToken::where('token_id', $payload['jti'])->where('revoked', 0)->exists();
        if(!$existsRevokedToken){
            throw new Exception('token has been revoked');
        }
    }

}
