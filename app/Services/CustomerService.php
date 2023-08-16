<?php

namespace App\Services;

use App\Models\Customer;
use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
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

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);

    }

    /** ດຶງ ຂໍ້ມູນລູກຄ້າ */
    public function listCustomers($request)
    {
        $perPage = $request->per_page;

        $query = Customer::select('customers.*');

         /** search name */
        $query = filterHelper::filterCustomerName($query, $request);

        $listCustomers = (clone $query)->orderBy('id', 'desc')->paginate($perPage);

        $listCustomers->transform(function ($item){
            return $item->format();
        });

        return response()->json([
            'listCustomers' => $listCustomers
        ], 200);
    }

    /** ແກ້ໄຂຂໍ້ມູນລູກຄ້າ */
    public function editCustomer($request)
    {
        if ($request->hasFile('logo')) {
            $editCustomer = Customer::find($request['id']);
            $editCustomer->company_name = $request['company_name'];
            $editCustomer->phone = $request['phone'];
            $editCustomer->email = $request['email'];
            $editCustomer->address = $request['address'];

                if ($request->hasFile('logo')) {
                    //dd('dasd');

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
        }

        if (is_string($request->logo)) {
            $editCustomer = filterHelper::logo($request);
        }

        if ($request->logo == null) {
            $editCustomer = filterHelper::logo($request);
        }

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ລຶບຂໍ້ມູນລູກຄ້າ */
    public function deleteCustomer($request)
    {
        $deleteCustomer = Customer::find($request['id']);
        $deleteCustomer->delete();

        /** Delete Image On Folder */
        CreateFolderImageHelper::deleteCustomer($deleteCustomer);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }
}
