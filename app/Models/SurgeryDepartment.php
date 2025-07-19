<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgeryDepartment extends Model
{
    protected $fillable = [
        'patient_id',
        'status',
        'surgeryType',
        'surgeryDate',
        'room_id',
        'bed_id',
        'notes',
        'dressing_changed',
        'wound_condition',
        'surgical_drains',
        'pain_level',
        'discharge_date'
    ];
    public function patient()
    {
        return $this->belongsTo(Patients::class);
    }
    public function doctors()
    {
        return $this->belongsToMany(DashboardAccounts::class, 'surgery_department_doctor', 'surgery_department_id', 'doctor_id')
            ->withTimestamps();
    }
    public function surgeryMeasurement()
    {
        return $this->hasMany(SurgeryMeasurement::class, 'surgery_department_id');
    }
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }
}
