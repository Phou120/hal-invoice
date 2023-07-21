<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Traits\ResponseAPI;
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
        $addUser->password = Hash::make('password');
        $addUser->save();

        $roleAdmin = Role::where('name', '=', 'admin')->first();
        $addUser->attachRoles([$roleAdmin]);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function listUser()
    {
        $listUser = DB::table('users')
        ->leftJoin('role_user', 'role_user.user_id', '=', '.id')
        ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
        ->select('users.id', 'users.name', 'users.email', 'users.created_at',
            DB::raw('GROUP_CONCAT(DISTINCT roles.name) as roles')
        )
        ->groupBy('users.id')
        ->orderBy('users.id')
        ->get();

        $userData = collect($listUser)->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => explode(',', $user->roles)
            ];
        })->values()->all();

        return response()->json([
            'items' => $userData
        ]);
    }

    public function editUser($request)
    {
        $editUser = User::find($request['id']);
        $editUser->name = $request['name'];
        $editUser->email = $request['email'];
        $editUser->save();

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function deleteUser($request)
    {
        $user = User::find($request['id']);
        if (!$user) {
            return response()->json([
                'message' => 'ບໍ່ພົບ user...'
            ], 404);
        }
        $user->roles()->detach();
        $user->delete();

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function changePassword($request)
    {
        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'msg' => 'ບໍ່ພົບ user...',
            ]);
        }
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }
}
