<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Radiologies extends Model
{
    protected $fillable = [
        'patient_id', // أضف هذا
        'title',
        'description',
        'media_files',
        'radiologies_date',
        'icup_id'
    ];
    public function patient()
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }

    public function intensiveCarePatient()
    {
        return $this->belongsTo(IntensiveCarePatient::class, 'icup_id');
    }
}
