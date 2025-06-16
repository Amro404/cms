<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRolesAndPermissions();
    }

    protected function setUpRolesAndPermissions(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::firstOrCreate(['name' => 'create content']);
        Permission::firstOrCreate(['name' => 'update content']);
        Permission::firstOrCreate(['name' => 'delete content']);
        Permission::firstOrCreate(['name' => 'publish content']);
        Permission::firstOrCreate(['name' => 'archive content']);
        Permission::firstOrCreate(['name' => 'manage users']);
        Permission::firstOrCreate(['name' => 'assign roles']);
        Permission::firstOrCreate(['name' => 'give permissions']);

        // Create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $editor = Role::firstOrCreate(['name' => 'editor']);
        $author = Role::firstOrCreate(['name' => 'author']);

        $admin->givePermissionTo(Permission::all());
        $editor->givePermissionTo(['create content', 'update content', 'publish content']);
        $author->givePermissionTo(['create content']);
    }
}
