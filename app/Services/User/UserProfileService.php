<?php

namespace App\Services\User;

use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserProfileService
{
    use ResponseAPI;

    public function ListUserProfile()
    {
        $user = Auth::user('api');

        $listUser = User::select(
            'users.*',
            DB::raw("CONCAT('" . config('services.master_path.user_profile') . "', users.profile) AS profile_url")
        )
        ->where('id', $user->id)
        ->first();

        return response()->json([
            'user' => $listUser
        ]);
    }
}
