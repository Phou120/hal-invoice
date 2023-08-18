<?php

namespace App\Services\User;

use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\returnData\ReturnService;

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
        ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
        ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
        ->leftJoin('permission_role', 'roles.id', '=', 'permission_role.role_id')
        ->leftJoin('permissions', 'permission_role.permission_id', '=', 'permissions.id')
        ->where('users.id', $user->id)
        ->first();

        $roleUser = $listUser->roles->pluck('name'); // Assuming the role name is stored in the 'name' column
        $permissionRole = $listUser->roles->flatMap(function ($role) {
            return $role->permissions->pluck('name');
        });

        /** return data */
        $response = (new ReturnService())->returnUserData($listUser, $roleUser, $permissionRole);

        return response()->json($response, 200);
    }
}
