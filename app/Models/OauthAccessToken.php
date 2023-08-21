<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;

class OauthAccessToken extends Model
{
    use HasFactory;


    public static function setOauthAccessToken($tokeId)
    {
        $user = Auth::user('api');

        $addAccessToken = new OauthAccessToken();
        $addAccessToken->token_id = $tokeId;
        $addAccessToken->user_id = $user->id;
        $addAccessToken->expires_at = Carbon::now()->addDay(7);
        $addAccessToken->revoked = 0;
        $addAccessToken->save();

        $refreshToken = new OauthRefreshToken();
        $refreshToken->token_id = $tokeId;
        $refreshToken->access_token_id = $addAccessToken->id;
        $refreshToken->expires_at = Carbon::now()->addDay(7);
        $refreshToken->revoked = 0;
        $refreshToken->save();
    }

    public static function revokedAccessToken()
    {
        $payload = auth()->payload();
        
        $oauthAccessTokens = OauthAccessToken::where('user_id', $payload['sub'])
        ->where('revoked', 0)
        ->get();
        if($oauthAccessTokens->isNotEmpty()){
            foreach($oauthAccessTokens as $item){
                $update = OauthAccessToken::find($item['id']);
                $update->revoked = 1;
                $update->save();
            }
        }
    }
}
