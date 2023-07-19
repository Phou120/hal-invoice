<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class company extends Model
{
    use HasFactory;
    use SoftDeletes;


    public function format()
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'url.logo' => config('services.file_path.company_logo') . $this->logo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
