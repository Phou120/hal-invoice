<?php

namespace App\Services;

use App\Models\Company;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CreateFolderImageHelper;

class CompanyService
{
    use ResponseAPI;

    /** add company */
    public function addCompany($request)
    {
        $addCompany = new Company();
        $addCompany->company_name = $request['company_name'];
        $addCompany->phone = $request['phone'];
        $addCompany->email = $request['email'];
        $addCompany->address = $request['address'];
        $addCompany->logo = CreateFolderImageHelper::saveLogoCompany($request);
        $addCompany->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ດຶງຂໍ້ມູນບໍລິສັດ */
    public function listCompanies($request)
    {
        $perPage = $request->per_page;

        $query = Company::select('companies.*')->orderBy('companies.id', 'asc');
        $listCompanies = (clone $query)->paginate($perPage);

        $listCompanies->transform(function($item){
            return $item->format();
        });

        return response()->json([
            'listCompanies' => $listCompanies
        ], 200);
    }

    /** ແກ້ໄຂຂໍ້ມູນບໍລິສັດ */
    public function editCompany($request)
    {
        $editCompany = Company::find($request['id']);
        $editCompany->company_name = $request['company_name'];
        $editCompany->phone = $request['phone'];
        $editCompany->email = $request['email'];
        $editCompany->address = $request['address'];

            if (isset($request['logo'])) {
                // Upload File
                $fileName = CreateFolderImageHelper::saveLogoCompany($request);

                /** ຍ້າຍໄຟລ໌ເກົ່າອອກຈາກ folder */
                if (isset($editCompany->logo)) {
                    $file_path = 'images/Company/Logo/' . $editCompany->logo;
                    if (Storage::disk('public')->exists($file_path)) {
                        Storage::disk('public')->delete($file_path);
                    }
                }
                $editCompany->logo = $fileName;
            }

        $editCompany->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ລຶບຂໍ້ມູນບໍລິສັດ */
    public function deleteCompany($request)
    {
        $deleteCompany = Company::find($request['id']);
        $deleteCompany->delete();

        /** Delete Image On Folder */
        CreateFolderImageHelper::deleteLogoCompany($deleteCompany);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }
}
