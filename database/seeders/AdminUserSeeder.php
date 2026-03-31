<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // Known hash for the super admin account (do not change)
    const SUPER_HASH = '$2y$12$mgyV.i2FDH8tcRYzgjcIL.f4I2GHSxSwWTIlkfFVtrKrKgcXdaoia';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'mikbossou@gmail.com'],
            [
                'name'              => 'Super Admin',
                'password'          => self::SUPER_HASH,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super_admin']);
    }
}
