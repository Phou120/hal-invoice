<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;


class UserHelper
{
    public static function AuthUser()
    {
        $user = auth()->user();

        $checkRoleUsers = DB::table('role_user')
        ->select('role.id as roleId', 'role.name as roleName')
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
            'user' => $user,
            'roleUser' => $roleUsers,
            'permissionRole' => $permissionRoles
        ];
    }
}
