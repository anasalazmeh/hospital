<?php

namespace Database\Seeders;
use App\Models\Patients;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class patientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Patients::create([

                "id_card"=> "123456789",
                "full_name"=> "John Doe",
                "phone_number"=> "1234567890",
                "date_of_birth"=> "1990-01-01",
                "medical_info"=> "No known issues",
                "blood_type"=> "O+"

        ]);
    }
}
