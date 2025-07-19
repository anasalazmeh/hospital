<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PediatricMeasurement extends Model
{
     use HasFactory;

    protected $fillable = [
        'pediatric_id',
        'doctor_id',
        'temperature',
        'heart_rate',
        'blood_pressure',
        'respiratory_rate',
        'oxygen_saturation',
        'glucose_level',
        'urine_output',
        'serum',
        'medications',
        'new_measurement'
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'oxygen_saturation' => 'decimal:2',
        'glucose_level' => 'decimal:2',
        'urine_output' => 'decimal:2',
    ];

    public function pediatric()
    {
        return $this->belongsTo(Pediatric::class,"pediatric_id");
    }
        public function doctor()
    {
        return $this->belongsTo(DashboardAccounts::class, 'doctor_id');
    }
}
