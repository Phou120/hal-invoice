<?php

namespace App\Services;

use App\Models\Customer;
use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CreateFolderImageHelper;
use Illuminate\Support\Facades\Auth;

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

    /** ດຶງ ຂໍ້ມູນລູກຄ້າ ໂດຍໃຊ້ paginate */
    public function listCustomers($request)
    {
        $perPage = $request->per_page;
        $searchTerm = $request->search;

        $query = Customer::select('customers.*');

         /** search name */
        $query = filterHelper::filterCustomerName($query, $searchTerm);

        $listCustomers = (clone $query)->orderBy('id', 'desc')->paginate($perPage);

        $listCustomers->transform(function ($item){
            return $item->format();
        });

        return response()->json([
            'listCustomers' => $listCustomers
        ], 200);
    }

    /** list customer to use skip */
    public function listCustomersSkip($request)
    {
        $query = Customer::orderBy('id', 'asc');

        /** count customer */
        $totalCustomers = $query->count();

        $data = (clone $query)->forPage($request['page'], $request['per_page'])->get();

        $myData = $data->skip($request['skip'])->take($request['per_page'])->values();

        $myData->transform(function ($item){
            return $item->format();
        });

        return response()->json([
            'totalCustomers' => $totalCustomers,
            'data' => $myData
        ], 200);
    }

    /** ແກ້ໄຂຂໍ້ມູນລູກຄ້າ */
    public function editCustomer($request)
    {
        $editCustomer = Customer::find($request['id']);
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
