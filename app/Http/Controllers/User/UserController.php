<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Services\User\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserRequest;

class UserController extends Controller
{
    public $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }


    public function addUser(UserRequest $request)
    {
        return $this->userService->addUser($request);
    }

    public function listUsers(Request $request)
    {
        return $this->userService->listUsers($request);
    }

    public function editUser(UserRequest $request)
    {
        return $this->userService->editUser($request);
    }

    public function deleteUser(UserRequest $request)
    {
        return $this->userService->deleteUser($request);
    }

    public function changePassword(UserRequest $request)
    {
        return $this->userService->changePassword($request);
    }
}
