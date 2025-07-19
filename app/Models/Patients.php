<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patients extends Model
{
    use HasFactory;
    protected $table = 'patients';
    protected $fillable = [
        'id_card',
        'full_name',
        'id_number',
        'phone',
        'date_of_birth',
        'blood_type',
        'status',
        'gender',
        'marital_status',
        'chronic_diseases',
        'email',
        'address',
        'height',
        'weight',
        'allergies',
        'current_medication',
        'profile_image',
        'emergency_contact_phone',
        'emergency_contact_name',
        'emergency_contact_relation',
        'kidney_status',
        'dialysis_days',
    ];
    protected $casts = [
        'status' => 'boolean',
    ];
    public function analyses()
    {
        return $this->hasMany(Analysis::class, 'patient_id');
    }
    public function radiologies()
    {
        return $this->hasMany(Radiologies::class, 'patient_id');
    }
    public function IntensiveCarePatient()
    {
        return $this->hasMany(IntensiveCarePatient::class);
    }
    public function doctorReport()
    {
        return $this->hasMany(DoctorReport::class, 'patient_id');
    }
    public function obstetricsGynecology()
    {
        return $this->hasMany(ObstetricsGynecology::class);
    }
    public function internalDepartments()
    {
        return $this->hasMany(InternalDepartment::class);
    }
    public function pediatrics()
    {
        return $this->hasMany(Pediatric::class);
    }
    public function prescriptions()
    {
        return $this->hasMany(Prescriptions::class, 'patient_id');
    }
}

