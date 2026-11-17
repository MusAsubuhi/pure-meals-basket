<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the first administrator from ADMIN_EMAIL, ADMIN_NAME and
 * ADMIN_PASSWORD in the environment. No account is created with built-in
 * credentials: if ADMIN_EMAIL or ADMIN_PASSWORD is missing, seeding stops.
 *
 * Run this before `php artisan config:cache`, since it reads the environment.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            throw new \RuntimeException(
                'Set ADMIN_EMAIL and ADMIN_PASSWORD in .env before seeding the first administrator.'
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'admin']);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'is_superadmin' => true,
            ]
        );

        if (! $user->hasRole('admin')) {
            $user->assignRole($role);
        }
    }
}
