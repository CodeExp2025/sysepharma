<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            DrugFormSeeder::class,
        ]);

        // User::factory(10)->create();

        // Create a default super admin user if needed, or just let the user handle it
        /*
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@pharma.com',
            'role_id' => \App\Models\Role::where('name', 'super_admin')->first()->id,
        ]);
        */
    }
}
