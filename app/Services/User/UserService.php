<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Str;
use App\Helpers\filterHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CreateFolderImageHelper;

class UserService
{
    use ResponseAPI;

    public function addUser($request)
    {
        $addUser = new User();
        $addUser->name = $request['name'];
        $addUser->email = $request['email'];
        $addUser->profile = CreateFolderImageHelper::saveUserProfile($request);
        $addUser->password = Hash::make($request['password']);
        $addUser->save();

        $roleAdmin = Role::where('name', '=', 'admin')->first();
        $addUser->attachRoles([$roleAdmin]);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function listUsers($request)
    {
        $perPage = $request->per_page;

        $query = User::select('users.id', 'users.name', 'users.email', 'users.created_at', 'users.profile') // Include the profile column
            ->leftJoin('role_user', 'role_user.user_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
            ->selectRaw('GROUP_CONCAT(DISTINCT roles.name) as roles')
            ->groupBy('users.id');

        $query = filterHelper::filterName($query, $request);

        $users = (clone $query)->orderBy('users.id', 'desc')->paginate($perPage);

        $userData = $users->map(function ($user) {
        $profileUrl = $user->profile;

        $fullProfileUrl = $profileUrl ? config('services.master_path.user_profile') . $profileUrl : null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile.url' => $fullProfileUrl,
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
        if ($request->hasFile('profile')) {
            $editUser = User::find($request['id']);
            $editUser->name = $request['name'];
            $editUser->email = $request['email'];

                if (isset($request['profile'])) {
                    // Upload File
                    $fileName = CreateFolderImageHelper::saveUserProfile($request);

                    /** ຍ້າຍໄຟລ໌ເກົ່າອອກຈາກ folder */
                    if (isset($editUser->profile)) {
                        $master_path = 'images/User/Profile/' . $editUser->profile;
                        if (Storage::disk('public')->exists($master_path)) {
                            Storage::disk('public')->delete($master_path);
                        }
                    }
                    $editUser->profile = $fileName;
                }

            $editUser->save();
        }

        if (is_string($request->profile)) {
            $editUser = filterHelper::userProfile($request);
        }

        if ($request->profile == null) {
            $editUser = filterHelper::userProfile($request);
        }

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

        /** Delete Image On Folder */
        CreateFolderImageHelper::deleteUserProfile($user);

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
