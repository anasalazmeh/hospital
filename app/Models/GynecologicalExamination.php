<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GynecologicalExamination extends Model
{
    use HasFactory;

    protected $table = 'gynecological_examinations';

    protected $fillable = [
        'obstetrics_gynecology_id',
        'doctor_id',
        'examination_type',
        'pregnancy_week',
        'fetal_heart_rate',
        'uterine_contractions',
        'cervical_dilation',
        'temperature',
        'blood_pressure',
        'fetal_movement',
        'fundal_height',
        'postpartum_bleeding',
        'ultrasound',
        'postpartum_monitoring',
        'medication_doses',
        'additional_procedures',
        'notes'
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'pregnancy_week' => 'integer',
        'fetal_movement' => 'integer',
    ];

    // Relationship with the main obstetrics record
    public function obstetricsRecord()
    {
        return $this->belongsTo(ObstetricsGynecology::class, 'obstetrics_gynecology_id');
    }

    // Relationship with the doctor who performed the exam
    public function doctor()
    {
        return $this->belongsTo(DashboardAccounts::class, 'doctor_id');
    }

    // Scope for prenatal exams
    public function scopePrenatal($query)
    {
        return $query->where('examination_type', 'prenatal');
    }

    // Scope for postnatal exams
    public function scopePostnatal($query)
    {
        return $query->where('examination_type', 'postnatal');
    }
}