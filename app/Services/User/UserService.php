<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\FuncCall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    use ResponseAPI;

    public function addUser($request)
    {
        $addUser = new User();
        $addUser->name = $request['name'];
        $addUser->email = $request['email'];
        $addUser->password = Hash::make($request['password']);
        $addUser->save();

        $roleAdmin = Role::where('name', '=', 'admin')->first();
        $addUser->attachRoles([$roleAdmin]);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function listUser($request)
    {
        $perPage = $request->per_page;

        $listUser = User::leftJoin('role_user', 'role_user.user_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->selectRaw('GROUP_CONCAT(DISTINCT roles.name) as roles')
            ->groupBy('users.id')
            ->orderByDesc('users.id')
            ->paginate($perPage);

        $userData = $listUser->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => explode(',', $user->roles)
            ];
        })->values();

        return response()->json([
            'listUser' => $userData
        ], 200);
    }

    public function editUser($request)
    {
        $editUser = User::find($request['id']);
        $editUser->name = $request['name'];
        $editUser->email = $request['email'];
        $editUser->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function deleteUser($request)
    {
        $user = User::find($request['id']);
        $user->email = $user->email . '_deleted_' . Str::random(6);
        $user->save();
        $user->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function changePassword($request)
    {
        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'error' => true,
                'msg' => 'ບໍ່ພົບ user...',
            ], 422);
        }
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }
}
