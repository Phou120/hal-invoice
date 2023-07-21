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

        return $addCompany;
    }

    /** ດຶງຂໍ້ມູນບໍລິສັດ */
    public function listCompanies()
    {
        $listCompanies = Company::select(
            'companies.*'
        )
        ->orderBy('companies.id', 'desc')->get();

        $listCompanies->transform(function($item){
            return $item->format();
        });

        return $listCompanies;
    }

    /** ແກ້ໄຂຂໍ້ມູນບໍລິສັດ */
    public function editCompany($request)
    {
        $editCompany = Company::findOrFail($request['id']);
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

        return $editCompany;
    }

    /** ລຶບຂໍ້ມູນບໍລິສັດ */
    public function deleteCompany($request)
    {
        $deleteCompany = Company::findOrFail($request['id']);
        $deleteCompany->delete();

        /** Delete Image On Folder */
        CreateFolderImageHelper::deleteLogoCompany($deleteCompany);

        return $deleteCompany;
    }
}
