<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObstetricsGynecology extends Model
{
  use HasFactory;

  protected $table = 'obstetrics_gynecology';

  protected $fillable = [
    'patient_id',
    'status',
    'gestational_weeks',
    'delivery_type',
    'delivery_date',
    'room_id',
    'bed_id',
    'discharge_date'
  ];

  protected $casts = [
    'delivery_date' => 'date',
    'discharge_date' => 'date',
  ];

  /**
   * العلاقة مع المريضة
   */
  public function patient()
  {
    return $this->belongsTo(Patients::class,'patient_id');
  }

  /**
   * العلاقة مع الفحوصات النسائية
   */
  public function examinations()
  {
    return $this->hasMany(GynecologicalExamination::class);
  }
    public function doctors()
  {
    return $this->belongsToMany(DashboardAccounts::class, 'obstetrics_gynecology_department_doctor', 'obstetrics_gynecology_id', 'doctor_id')
      ->withTimestamps();
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
