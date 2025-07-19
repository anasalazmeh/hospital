<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pediatric extends Model
{
    protected $fillable = [
        'status',
        'room_id',
        'bed_id',
        'ecg',
        'vaccinations',
        'height',
        'weight',
        'patient_id',
        'discharge_date'
    ];

    protected $casts = [
        'ecg' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patients::class);
    }
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }
    public function PediatricMeasurements()
    {
        return $this->hasMany(PediatricMeasurement::class);
    }
    public function doctors()
    {
        return $this->belongsToMany(DashboardAccounts::class, 'pediatrics_department_doctor', 'pediatrics_id', 'doctor_id')
            ->withTimestamps();
    }
}
