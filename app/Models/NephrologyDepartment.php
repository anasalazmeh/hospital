<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NephrologyDepartment extends Model
{
    protected $table = 'nephrology_departments';
    protected $fillable = [
        'patient_id',
        'primary_diagnosis',
        'secondary_diagnosis',
        'kidney_status',
        'dialysis_type',
        'dialysis_days'
    ];
    public function patient()
    {
        return $this->belongsTo(Patients::class);
    }
        public function nephrologyMeasurement()
    {
        return $this->hasMany(NephrologyMeasurement::class, 'nephrology_department_id');
    }
}
