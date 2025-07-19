<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorReport extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'icup_id',
        'patient_id',
        'department',
        'report',
        'type',
    ];
    // العلاقة مع جدول المستخدمين
    public function doctor()
    {
        return $this->belongsTo(DashboardAccounts::class, 'user_id');
    }
    public function patient()
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }
    public function intensiveCarePatient()
    {
        return $this->belongsTo(IntensiveCarePatient::class, 'icup_id');
    }
}
