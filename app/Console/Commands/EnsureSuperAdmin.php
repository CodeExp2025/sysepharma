<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EnsureSuperAdmin extends Command
{
    protected $signature   = 'admin:ensure-super';
    protected $description = 'Restore the super_admin account if it has been deleted or is missing.';

    const SUPER_EMAIL = 'mikbossou@gmail.com';
    const SUPER_NAME  = 'Super Admin';
    const SUPER_HASH  = '$2y$12$mgyV.i2FDH8tcRYzgjcIL.f4I2GHSxSwWTIlkfFVtrKrKgcXdaoia';

    public function handle(): void
    {
        $exists = User::where('email', self::SUPER_EMAIL)->exists();

        if (! $exists) {
            $user = User::create([
                'id'                => 1,
                'name'              => self::SUPER_NAME,
                'email'             => self::SUPER_EMAIL,
                'password'          => self::SUPER_HASH,
                'email_verified_at' => now(),
                'pharmacy_id'       => null,
                'depot_id'          => null,
            ]);

            $user->syncRoles(['super_admin']);
            $this->info('Super admin account restored successfully.');
        } else {
            // Ensure role is still assigned
            $user = User::where('email', self::SUPER_EMAIL)->first();
            if (! $user->hasRole('super_admin')) {
                $user->syncRoles(['super_admin']);
                $this->info('Super admin role re-assigned.');
            } else {
                $this->info('Super admin account is healthy.');
            }
        }
    }
}
