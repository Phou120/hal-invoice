<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Helpers\UserHelper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\OauthAccessToken;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;


class AuthController extends Controller
{
     /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'logout', 'refresh']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'ລະຫັດຜ່ານ ຫຼື ອີເມວບໍ່ຖືກຕ້ອງ...'], 401);
        }
        
        $customClaims = [
            'type' => 'access_token',
            'refres_token_id' => null
        ];
        $customToken = JWTAuth::claims($customClaims)->fromUser(JWTAuth::user());
        $payload = JWTAuth::setToken($customToken)->getPayload();

        OauthAccessToken::setOauthAccessToken($payload['jti']);
        
        return $this->respondWithToken($customToken, $payload);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'successfully logged out'],200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        // $date = Carbon::now()->timestamp;
        try {
            $data = auth()->payload()->toArray();
            if (array_key_exists('type', $data) && $data['type'] === 'access_token') {
                return response()->json(['message' => 'token ນີ້ບໍ່ສາມາດໃຊ້ refresh.'], 401);
            }
            
            DB::beginTransaction();
                /** Revoked Token */
                OauthAccessToken::revokedAccessToken();

                $currentToken = JWTAuth::getToken();
                JWTAuth::refresh($currentToken);

                $customClaims = [
                    'type' => 'access_token',
                    'refres_token_id' => null
                ];
                $customToken = JWTAuth::claims($customClaims)->fromUser(JWTAuth::user());

                $payload = JWTAuth::setToken($customToken)->getPayload();

                /** Set Token To Database */
                OauthAccessToken::setOauthAccessToken($payload['jti']);
            DB::commit();
            
            return $this->respondWithToken($customToken, $payload);
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'refresh token has expired.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $payload)
    {
        $customClaims = [
            'type' => 'refresh',
            'refres_token_id' => $payload['jti'],
            'exp' => now()->addDays(14)->timestamp,
        ];
        $refreshToken = JWTAuth::customClaims($customClaims)->fromUser(JWTAuth::user());


        /** Response Json */
        return response()->json([
            'token_type' => 'bearer',
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => JWTAuth::factory()->getTTL(),
            'auth' => UserHelper::AuthUser()
        ], 200);
    }
}
