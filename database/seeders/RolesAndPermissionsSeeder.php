<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::firstOrCreate(['name' => 'create content']);
        Permission::firstOrCreate(['name' => 'update content']);
        Permission::firstOrCreate(['name' => 'delete content']);
        Permission::firstOrCreate(['name' => 'publish content']);
        Permission::firstOrCreate(['name' => 'archive content']);

        // Create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $editor = Role::firstOrCreate(['name' => 'editor']);
        $author = Role::firstOrCreate(['name' => 'author']);

        $admin->givePermissionTo(Permission::all());
        $editor->givePermissionTo(['create content', 'update content', 'publish content']);
        $author->givePermissionTo(['create content']);

        // Create an admin user and assign admin role
        $adminUser = User::firstOrCreate(
            [ 'email' => 'admin@example.com' ],
            [ 'name' => 'Admin', 'password' => Hash::make('password') ]
        );
        $adminUser->assignRole('admin');
    }
}
