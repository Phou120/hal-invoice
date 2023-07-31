<?php

namespace App\Services\CompanyUser;

use App\Models\Role;
use App\Models\User;
use App\Models\CompanyUser;
use App\Traits\ResponseAPI;
use Illuminate\Support\Str;
use App\Helpers\TableHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyUserService
{
    use ResponseAPI;

    public function createCompanyUser($request)
    {
        DB::beginTransaction();

            /** add user  */
            $addUser = new User();
            $addUser->name = $request['name'];
            $addUser->email = $request['email'];
            $addUser->password = Hash::make($request['password']);
            $addUser->save();

            // where column name of JobSeeker
            $role = Role::where('name', '=', 'admin')->first();
            $addUser->attachRoles([$role]);

            $createCompanyUser = new CompanyUser();
            $createCompanyUser->company_id = $request['company_id'];
            $createCompanyUser->user_id = $addUser['id'];
            $createCompanyUser->save();

        DB::commit();

        return response()->json([
            'errors' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function listCompanyUser()
    {
        $listCompanyUser = CompanyUser::select('company_users.*')
        ->leftJoin('companies as company', 'company_users.company_id', 'company.id')
        ->leftJoin('users as user', 'company_users.user_id', 'user.id')
        ->orderBy('id', 'desc')->get();

        $listCompanyUser->map(function ($item){
            /** loop data */
            TableHelper::loopDataInCompanyUser($item);
        });

        return response()->json([
            'listCompanyUser' => $listCompanyUser
        ], 200);
    }

    public function updateCompanyUser($request)
    {
        $update = CompanyUser::find($request['id']);
        $update->company_id = $request['company_id'];

        /** update User */
        $getUser = User::find($update['user_id']);
        if(($getUser)){
            $getUser->name = $request['name'];
            $getUser->email = $request['email'];
            $getUser->save();
        }else{
            return response()->json(['msg' =>'ບໍ່ພົບ user...'], 422);
        }

        return response()->json(['errors' => false, 'msg' => 'ສຳເລັດແລ້ວ'], 200);
    }

    public function deleteCompanyUser($request)
    {
        try {

            DB::beginTransaction();

                // Find the CompanyUser id
                $deleteCompanyUser = CompanyUser::findOrFail($request['id']);
                $deleteCompanyUser->delete();

                // Find the User id
                $deleteUser = User::find($deleteCompanyUser['user_id']);
                $deleteUser->email = $deleteUser->email . '_deleted_' . Str::random(6);
                $deleteUser->save();
                $deleteUser->delete();

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'msg' => 'ບໍ່ສາມາດລຶບລາຍການນີ້ໄດ້...'
            ], 422);
        }
    }
}
