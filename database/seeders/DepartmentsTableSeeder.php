<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                $departments = [
   [
                'name' => 'قسم العناية المشددة',
                'description' => 'قسم العناية المركزة للمرضى الذين يحتاجون مراقبة مستمرة',
                'status' => true,
                'created_at'=>now(),
                'updated_at' =>now()
            ],
            [
                'name' => 'قسم الأطفال',
                'description' => 'قسم خاص بعلاج الأطفال',
                'status' => true,
                'created_at'=>now(),
                'updated_at' =>now()
            ],
            [
                'name' => 'قسم النسائية',
                'description' => 'قسم خاص بتوليد وامراض نسائية',
                'status' => true,
                'created_at'=>now(),
                'updated_at' =>now()
            ],
            [
                'name' => 'قسم الجراحة',
                'description' => 'قسم العمليات الجراحية',
                'status' => true,
                'created_at'=>now(),
                'updated_at' =>now()
            ],
            [
                'name' => 'قسم الداخلية',
                'description' => 'قسم الأمراض الباطنية',
                'status' => true,
                'created_at'=>now(),
                'updated_at' =>now()
            ], 
            [
                'name' => 'قسم الكلى',
                'description' => 'قسم أمراض الكلى وغسيل الكلى', 
                'status' => true,
                'created_at'=>now(),
                'updated_at' =>now()
            ]
        ];

        DB::table('departments')->insert($departments);
    }
}
// php artisan db:seed --class=DepartmentsTableSeeder