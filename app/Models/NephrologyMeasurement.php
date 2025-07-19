<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NephrologyMeasurement extends Model
{
        protected $table = 'nephrology_measurements';
        protected $fillable = [
        'nephrology_department_id',
        "doctor_id",
        'weight',
        'height',
        'blood_pressure',
        'pulse',
        'temperature',
        'creatinine',
        'urea',
        'gfr',
        'sodium',
        'potassium',
        'notes',
        'type_mmeasurements'
    ];

    /**
     * الحقول التي يجب إخفاؤها عند التحويل إلى مصفوفة أو JSON.
     *
     * @var array
     */

    /**
     * الحقول التي يجب تحويل أنواعها.
     *
     * @var array
     */
    protected $casts = [
        'weight' => 'float',
        'height' => 'float',
        'pulse' => 'integer',
        'temperature' => 'float',
        'creatinine' => 'float',
        'urea' => 'float',
        'gfr' => 'float',
        'sodium' => 'float',
        'potassium' => 'float',
    ];
    public function nephrologyDepartment()
    {
        return $this->belongsTo(NephrologyDepartment::class);
    }
    public function doctor()
{
    return $this->belongsTo(DashboardAccounts::class, 'doctor_id');
}
}
