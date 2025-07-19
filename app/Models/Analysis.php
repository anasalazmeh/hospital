<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Analysis extends Model
{
    use HasFactory;

    protected $table = 'analyses';
    
    protected $fillable = [
        'patient_id',
        'icup_id',
        'title',
        'description',
        'pdf_path',
        'analysis_date'
    ];
    
    protected $casts = [
        'analysis_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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