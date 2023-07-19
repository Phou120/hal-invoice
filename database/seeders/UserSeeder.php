<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createRoles();
        $this->CreatePermissions();
    }

    // role
    public function createRoles(){
        $roleSuperAdmin = Role::create([
            'name' => 'superAdmin', 'display_name' => 'Super Admin'
        ]);

        $roleAdmin  = Role::create([
            'name' => 'admin', 'display_name' => 'admin'
        ]);

        //Create User
        $createUserSuperAdmin = User::create([
            'name' => 'admin',
            'email' => 'super@gmail.com',
            'password' => Hash::make('super@invoice2023g'),
        ]);
        $createUserSuperAdmin->attachRoles([$roleSuperAdmin]);

        $createUserAdmin = User::create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin@invoice2023g'),
        ]);
        $createUserAdmin->attachRoles([$roleAdmin]);
    }

    public function CreatePermissions(){
        Permission::create(['name' => 'add', 'display_name' => 'Add']);
        Permission::create(['name' => 'edit', 'display_name' => 'edit']);
        Permission::create(['name' => 'view', 'display_name' => 'view']);
        Permission::create(['name' => 'delete', 'display_name' => 'delete']);

    }
}
