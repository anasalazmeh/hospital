<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    use HasFactory;

    /**
     * الحقول التي يمكن تعبئتها بشكل جماعي
     *
     * @var array
     */
    protected $fillable = [
        'department_id',
        'room_id',
        'bed_number',
        'status',
        'is_active'
    ];

    /**
     * نوع البيانات للحقول الخاصة
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * القيم الافتراضية للحقول
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'available',
        'is_active' => true,
    ];

    /**
     * العلاقة مع جدول الأقسام (Department)
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * العلاقة مع جدول الغرف (Room)
     */
    public function room()
    {
        return $this->belongsTo(Room::class,'room_id');
    }

    /**
     * نطاق الاستعلام للأسرة النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق الاستعلام للأسرة المتاحة فقط
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * نطاق الاستعلام للأسرة المشغولة فقط
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    /**
     * نطاق الاستعلام للأسرة التابعة لقسم معين
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
    // public function getStatusAttribute($value)
    // {
    //     return [
    //         'available' => 'متاحة',
    //         'occupied' => 'مشغولة',
    //     ][$value] ?? $value;
    // }
}