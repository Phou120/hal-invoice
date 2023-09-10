<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyUser extends Model
{


    public function company()
    {   
        return $this->belongsTo(Company::class);
    }

    use HasFactory;
    use SoftDeletes;
}
