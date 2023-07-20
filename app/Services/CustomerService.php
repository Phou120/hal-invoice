<?php

namespace App\Services;

use App\Models\Customer;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CreateFolderImageHelper;

class CustomerService
{
    use ResponseAPI;


    /******* add customer *******/
    public function addCustomer($request)
    {
        $addCustomer = new Customer();
        $addCustomer->company_name = $request['company_name'];
        $addCustomer->phone = $request['phone'];
        $addCustomer->email = $request['email'];
        $addCustomer->logo = CreateFolderImageHelper::saveImage($request);
        $addCustomer->address = $request['address'];
        $addCustomer->save();

        return $addCustomer;

    }

    /** ດຶງ ຂໍ້ມູນລູກຄ້າ */
    public function listCustomers()
    {
        $listCustomers = Customer::select(
            'customers.*'
        )
        ->orderBy('customers.id', 'desc')->get();
        $listCustomers->transform(function ($item){
            return $item->format();
        });

        return $listCustomers;
    }

    /** ແກ້ໄຂຂໍ້ມູນລູກຄ້າ */
    public function editCustomer($request)
    {
        $editCustomer = Customer::findOrFail($request['id']);
        $editCustomer->company_name = $request['company_name'];
        $editCustomer->phone = $request['phone'];
        $editCustomer->email = $request['email'];
        $editCustomer->address = $request['address'];

            if (isset($request['logo'])) {

                // Upload File
                $fileName = CreateFolderImageHelper::saveImage($request);

                /** ຍ້າຍໄຟລ໌ເກົ່າອອກຈາກ folder */
                if (isset($editCustomer->logo)) {
                    $destination_path = 'images/Customer/Logo/' . $editCustomer->logo;
                    if (Storage::disk('public')->exists($destination_path)) {
                        Storage::disk('public')->delete($destination_path);
                    }
                }
                $editCustomer->logo = $fileName;
            }

        $editCustomer->save();

        return $editCustomer;
    }

    /** ລຶບຂໍ້ມູນລູກຄ້າ */
    public function deleteCustomer($request)
    {
        $deleteCustomer = Customer::findOrFail($request['id']);
        $deleteCustomer->delete();

        /** Delete Image On Folder */
        CreateFolderImageHelper::deleteCustomer($deleteCustomer);

        return $deleteCustomer;
    }
}
