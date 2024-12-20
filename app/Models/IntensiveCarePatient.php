<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntensiveCarePatient extends Model
{
    use HasFactory;
    protected $table = 'intensive_care_patients';
    protected $fillable = [
        'id_patients', 'id_card', 'specialties', 'health_condition', 'room_number',
        'bed_number', 'id_measurements_and_surgeries', 'doctor_report', 'discharge_date'
    ];

    public function measurementAndDose()
    {
        return $this->hasMany(MeasurementAndDose::class, "id",'id_measurements_and_surgeries');
    }
    public function patients()
    {
        return $this->hasMany(Patients::class, 'id',"id_patients");
    }
}

