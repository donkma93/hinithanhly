<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class PromoteAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@kygui.local')->first();

        if ($admin) {
            $admin->syncRoles(['super-admin']);
            $this->command->info('✓ Admin user promoted to super-admin role');
        } else {
            $this->command->error('✗ Admin user not found');
        }
    }
}
