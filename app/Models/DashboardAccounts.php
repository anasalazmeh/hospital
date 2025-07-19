<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
class DashboardAccounts extends Authenticatable implements JWTSubject
{
    use HasFactory;
    protected $table = 'user_accounts';
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'pin',
        'role',
        'id_card',
        'last_device_id',
        'is_active',
        'force_password_reset',
        'gender',
        'birth_date',
        'address',
        'blood_type',
        'department_id',
        'first_login',
        'verification_code',
        'verification_code_expires_at'
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function specialties()
    {
        return $this->belongsToMany(Specialties::class, 'user_specialty', 'user_account_id', 'specialty_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function intensiveCarePatient()
    {
        return $this->belongsToMany(IntensiveCarePatient::class, 'icp_user', 'user_account_id', 'intensive_care_patients_id');
    }
    public function doctorReports()
    {
        return $this->hasMany(DoctorReport::class);
    }
    public function internalDepartments()
    {
        return $this->belongsToMany(InternalDepartment::class, 'internal_department_doctor', 'doctor_id', 'internal_department_id')
            ->withTimestamps();
    }
    public function surgeryDepartments()
    {
        return $this->belongsToMany(SurgeryDepartment::class, 'surgery_department_doctor', 'doctor_id', 'surgery_department_id')
            ->withTimestamps();
    }
    public function internalMeasurement()
    {
        return $this->hasMany(InternalMeasurement::class);
    }
    public function nephrologyMeasurement()
    {
        return $this->hasMany(NephrologyMeasurement::class);
    }

}
