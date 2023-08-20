<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserHelper
{
    public static function AuthUser()
    {
        $user = Auth::user('api');

        $listUser = User::select(
            'users.*',
            DB::raw("CONCAT('" . config('services.master_path.user_profile') . "', users.profile) AS profile_url")
        )->where('users.id', $user->id)
        ->first();

        $checkRoleUsers = DB::table('role_user')
        ->select('role.id as roleId', 'role.name as roleName',)
        ->leftJoin('roles as role', 'role.id', '=', 'role_user.role_id')
        ->where('user_id', $user->id)->get();

        $roleUsers = $checkRoleUsers->pluck('roleName');

        $permissionRoles = DB::table('permission_role')
        ->select('permissions.name as permissionName')
        ->leftJoin('permissions', 'permissions.id', '=', 'permission_role.permission_id')
        ->whereIn('permission_role.role_id', $checkRoleUsers->pluck('roleId'))
        ->groupBy('permission_role.permission_id')->get()
        ->pluck('permissionName');

        return [
            'user' => [
                'id' => $listUser->id, // Add user ID here
                'name' => $listUser->name,
                'email' => $listUser->email,
                'profile_url' => $listUser->profile_url,
                'tel' => $listUser->tel,
                'created_at' => $listUser->created_at,
                'updated_at' => $listUser->updated_at,
                'roleUser' => $roleUsers,
                'permissionRole' => $permissionRoles,
            ]
        ];
    }
}
