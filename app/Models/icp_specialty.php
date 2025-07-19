<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class icp_specialty extends Model
{
    use HasFactory;
    protected $table = 'specialties';
    protected $fillable = [
      'intensive_care_patient_id','specialty_id'
    ];
}
