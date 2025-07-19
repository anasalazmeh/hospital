<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntensiveCarePatient extends Model
{
    use HasFactory;
    protected $table = 'intensive_care_patients';
    protected $fillable = [
        'id_patients',
        'id_card',
        'health_condition',
        'room_id',
        'bed_id',
        'discharge_date',
        'admission_reason',
        'attending_doctor_id',
        'severity_level',
        'medical_notes',
        'ventilator_dependency',
        'isolation_required'
    ];
    protected $casts = [
        'ventilator_dependency' => 'boolean',
        'discharge_date' => 'date'
    ];



    public function patient()
    {
        return $this->belongsTo(Patients::class, 'id_patients');
    }
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
    public function bed()
    {
        return $this->belongsTo(Bed::class, 'bed_id');
    }
    // تحويل التخصصات المخزنة إلى مصفوفة
    public function getSpecialtiesArrayAttribute()
    {
        return explode(',', $this->specialties); // تحويل القيم المفصولة إلى مصفوفة
    }
    public function assignSpecialties($specialties)
    {
        // تخزين التخصصات كقيم مفصولة
        $this->specialties = implode(',', $specialties);
        $this->save();
    }
    public function specialties()
    {
        return $this->belongsToMany(Specialties::class, 'icps', 'intensive_care_patient_id', 'specialty_id');
    }
    public function user_accounts()
    {
        return $this->belongsToMany(
            DashboardAccounts::class,
            'icp_user',
            'intensive_care_patients_id',
            'user_account_id'
        );
    }
    public function analyses()
    {
        return $this->hasMany(Analysis::class, 'icup_id');
    }
    public function radiologies()
    {
        return $this->hasMany(Radiologies::class, 'icup_id');
    }
    public function doctorReport()
    {
        return $this->hasMany(DoctorReport::class, 'icup_id');
    }
    public function measurementAndDose()
    {
        return $this->hasMany(MeasurementAndDose::class, 'icup_id');
    }
}

