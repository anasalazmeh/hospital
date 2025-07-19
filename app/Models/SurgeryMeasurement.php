<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgeryMeasurement extends Model
{
    protected $fillable = [
        'surgery_department_id',
        'doctor_id',
        'dressing_changed',
        'wound_condition',
        'surgical_drains',
        'pain_level',
        'temperature',
        'blood_pressure',
        'oxygen_level',
        'heart_rate',
        'respiration_rate',
        'blood_sugar',
        'medication_doses'
    ];

    public function surgeryDepartment()
    {
        return $this->belongsTo(SurgeryDepartment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patients::class);
    }
    public function doctor()
    {
        return $this->belongsTo(DashboardAccounts::class, 'doctor_id');
    }
}
