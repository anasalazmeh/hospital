<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeasurementAndDose extends Model
{
    use HasFactory;
    // تحديد اسم الجدول
    protected $table = 'measurements_and_doses';
    protected $fillable = [
        'blood_pressure',
        'blood_sugar',
        'temperature',
        'blood_analysis',
        'urine_output',
        'doses',
        'oxygen_level',
    ];
    public function intensiveCarePatient()
    {
        return $this->belongsTo(IntensiveCarePatient::class, 'id_measurements_and_surgeries');
    }
}
