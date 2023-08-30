<?php

namespace App\Services\CompanyUser;

use App\Traits\ResponseAPI;
use App\Models\CompanyBankAccount;


class CompanyBankAccountService
{
    use ResponseAPI;

    public function createCompanyBankAccount($request)
    {
        $createBankAccount = new CompanyBankAccount();
        $createBankAccount->company_id = $request['company_id'];
        $createBankAccount->bank_name = $request['bank_name'];
        $createBankAccount->account_name = $request['account_name'];
        $createBankAccount->account_number = $request['account_number'];
        $createBankAccount->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function listCompanyBankAccount($request)
    {
        $perPate = $request->per_page;

        $query = CompanyBankAccount::select('company_bank_accounts.*');

        $companyBankAccount = (clone $query)->orderBy('id', 'asc')->paginate($perPate);

        return response()->json([
            'companyBankAccount' => $companyBankAccount
        ]);
    }

    public function updateCompanyBankAccount($request)
    {
        $updateBankAccount = CompanyBankAccount::find($request['id']);
        $updateBankAccount->company_id = $request['company_id'];
        $updateBankAccount->bank_name = $request['bank_name'];
        $updateBankAccount->account_name = $request['account_name'];
        $updateBankAccount->account_number = $request['account_number'];
        $updateBankAccount->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function deleteCompanyBankAccount($request)
    {
        $deleteBankAccount = CompanyBankAccount::find($request['id']);
        $deleteBankAccount->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function updateStatusBankAccount($request)
    {
        $updateStatus = CompanyBankAccount::find($request['id']);
        $updateStatus->status = $request['status'];
        $updateStatus->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }
}
