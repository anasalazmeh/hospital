<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    /**
     * الحقول التي يمكن تعبئتها بشكل جماعي (Mass Assignment)
     *
     * @var array
     */
    protected $fillable = [
        'department_id',
        'room_number',
        'capacity',
        'status',
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean' // لضمان أن الحالة تُحفظ كـ true/false
    ];
    /**
     * القيم الافتراضية لسمات النموذج
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'available',
    ];

    /**
     * العلاقة مع جدول الأقسام (Department)
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    /**
     * العلاقة مع جدول الأسرة (Beds)
     */
    public function beds()
    {
        return $this->hasMany(Bed::class);
    }


    /**
     * نطاق الاستعلام للغرف المتاحة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * نطاق الاستعلام للغرف المشغولة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    /**
     * الحصول على حالة الغرفة بشكل مقروء
     *
     * @return string
     */
    // public function getStatusAttribute($value)
    // {
    //     return [
    //         'available' => 'متاحة',
    //         'occupied' => 'مشغولة',
    //     ][$value] ?? $value;
    // }

    /**
     * التحقق مما إذا كانت الغرفة متاحة
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->status === 'available';
    }

    /**
     * التحقق مما إذا كانت الغرفة مشغولة
     *
     * @return bool
     */
    public function isOccupied()
    {
        return $this->status === 'occupied';
    }
    public function getDynamicStatusAttribute()
    {
        $occupiedBeds = $this->beds()->where('status', 'occupied')->count();
        $totalBeds = $this->beds()->count();

        return ($occupiedBeds === $totalBeds && $totalBeds > 0) ? 'occupied' : 'available';
    }
}