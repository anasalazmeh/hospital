<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalDepartment extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_id',
    'status',
    'room_id',
    'bed_id',
    'notes',
    'discharge_date',
    'ecg',
  ];
    protected $casts = [
        'ecg' => 'boolean',
    ];
  public function patient()
  {
    return $this->belongsTo(Patients::class, 'patient_id');
  }

  public function doctors()
  {
    return $this->belongsToMany(DashboardAccounts::class, 'internal_department_doctor', 'internal_department_id', 'doctor_id')
      ->withTimestamps();
  }
  public function internalMeasurement()
  {
    return $this->hasMany(InternalMeasurement::class, 'internal_id');
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