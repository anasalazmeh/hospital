<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalMeasurement extends Model
{
    protected $table = 'internal_department_measurements';
    protected $fillable = [
        'internal_id',
        'doctor_id',
        'temperature',
        'blood_pressure',
        'oxygen_level',
        'heart_rate',
        'respiration_rate',
        'blood_sugar',
        'weight',
        'blood_tests',
        'medication_doses',
        'medical_procedures',
        'ecg',
        'kidney_functions',
        'liver_functions',
        'blood_count',
        'new_measurement',
        'notes'
    ];

    public function internalDepartment()
    {
        return $this->belongsTo(InternalDepartment::class,'internal_id');
    }
    public function doctor()
    {
        return $this->belongsTo(DashboardAccounts::class);
    }
}
