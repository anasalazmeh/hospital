<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescriptions extends Model
{
    use HasFactory;

    /**
     * الحقول التي يمكن تعبئتها جماعياً
     *
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'prescription_text',
        'notes',
    ];

    /**
     * الحقول التي يجب إخفاؤها عند التحويل إلى مصفوفة أو JSON
     *
     * @var array
     */

    /**
     * العلاقة مع نموذج المريض
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function patient()
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }

    /**
     * العلاقة مع نموذج الطبيب
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function doctor()
    {
        return $this->belongsTo(DashboardAccounts::class, 'doctor_id');
    }

    /**
     * الحصول على النص المنسق للوصفة الطبية
     *
     * @return string
     */
    public function getFormattedPrescriptionAttribute()
    {
        return nl2br($this->prescription_text);
    }

}