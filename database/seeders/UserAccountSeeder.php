<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DashboardAccounts;
class UserAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DashboardAccounts::create([
            'full_name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'id_card' => 'eb56d70',
            'pin' => bcrypt('000000'), // Hash the PIN
            'phone' => '+1234567890',
            'force_password_reset' => true,
            'birth_date' => '2001-1-1',
            'gender' => 'male',
            'is_active' => true ,
            'first_login' => true,
            'created_at'=>now(),
            'updated_at' =>now()
        ]);
    }
}
// php artisan db:seed --class=UserAccountSeeder