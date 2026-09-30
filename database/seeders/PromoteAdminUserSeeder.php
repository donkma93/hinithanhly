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
        $admin = User::where('email', 'admin@cohankygui.local')->first();

        if ($admin) {
            $admin->syncRoles(['super-admin']);
            $this->command->info('âœ“ Admin user promoted to super-admin role');
        } else {
            $this->command->error('âœ— Admin user not found');
        }
    }
}

