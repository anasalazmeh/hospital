<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patients extends Model
{
    use HasFactory;
    protected $table = 'patients';
    protected $fillable = [
        'id_card', 'full_name', 'id_number', 'phone_number', 
        'date_of_birth', 'medical_info', 'blood_type', 'card_status'
    ];

}

