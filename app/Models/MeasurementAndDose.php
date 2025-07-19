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
        'temperature',
        'blood_pressure',
        'oxygen_level',
        'blood_sugar',
        'heart_rate',
        'respiratory_rate',
        'urine_output',
        'cvp',
        'doses',
        'serone',
        'echocardiography_results',
        'echo_findings_results',
        'requires_dialysis',
        'additional_procedures',
        'icup_id',
        'user_account_id'
    ];

    protected $casts = [
        'requires_dialysis' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];
    public function intensiveCarePatient()
    {
        return $this->belongsTo(IntensiveCarePatient::class, 'icup_id');
    }
    public function user()
    {
        return $this->belongsTo(DashboardAccounts::class, 'user_account_id');
    }

}
