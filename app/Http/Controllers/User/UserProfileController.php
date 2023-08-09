<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\User\UserProfileService;

class UserProfileController extends Controller
{
    public $userProfileService;

    public function __construct(UserProfileService $userProfileService)
    {
        $this->userProfileService = $userProfileService;
    }


    public function ListUserProfile()
    {
        return $this->userProfileService->ListUserProfile();
    }
}
