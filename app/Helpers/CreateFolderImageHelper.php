<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;


class CreateFolderImageHelper
{

    /***** save image *****/
    public static function saveImage($request)
    {
        if ($request->hasFile('logo')) {
            $destination_path = '/images/Customer/Logo';
            $imageFile = $request->file('logo');

            //get just text
            $extension = $imageFile->getClientOriginalExtension();

            //Filename to storage
            $filename = 'customer_logo' . '_' . time() . '.' . $extension;
            Storage::disk('public')->putFileAs($destination_path, $imageFile, $filename);

            return $filename;
        }
    }

    public static function deleteCustomer($deleteCustomer)
    {
        //Delete File in folder
        if (isset($deleteCustomer->logo)) {
            $destination_path = 'images/Customer/Logo/' . $deleteCustomer->logo;
            if (Storage::disk('public')->exists($destination_path)) {
                Storage::disk('public')->delete($destination_path);
            }
        }
    }


    public static function saveLogoCompany($request)
    {
        if ($request->hasFile('logo')) {
            $file_path = '/images/Company/Logo';
            $imageFile = $request->file('logo');

            //get just text
            $extension = $imageFile->getClientOriginalExtension();

            //Filename to storage
            $filename = 'company_logo' . '_' . time() . '.' . $extension;
            Storage::disk('public')->putFileAs($file_path, $imageFile, $filename);

            return $filename;
        }
    }


    public static function deleteLogoCompany($deleteCompany)
    {
        //Delete File in folder
        if (isset($deleteCompany->logo)) {
            $file_path = 'images/Company/Logo/' . $deleteCompany->logo;
            if (Storage::disk('public')->exists($file_path)) {
                Storage::disk('public')->delete($file_path);
            }
        }
    }

}
