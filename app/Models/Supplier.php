<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    /**
     * اسم الجدول المرتبط بالنموذج.
     *
     * @var string
     */
    protected $table = 'suppliers';

    /**
     * الحقول التي يمكن تعبئتها.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'contact_person'
    ];

    /**
     * الحقول التي يجب إخفاؤها عند التحويل إلى JSON.
     *
     * @var array
     */

    /**
     * تحويل أنواع الحقول.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * العلاقة مع جدول الأصناف (إذا كان المورد يوفر أصنافاً).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'supplier_id');
    }
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'supplier_id');
    }

    /**
     * العلاقة مع جدول المشتريات.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * نطاق البحث بالموردين النشطين.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق البحث بالاسم.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', '%'.$name.'%');
    }

    /**
     * وصول سريع للمعلومات الأساسية.
     *
     * @return array
     */
    public function getBasicInfoAttribute()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'contact_person' => $this->contact_person
        ];
    }

    /**
     * التحقق من وجود بريد إلكتروني.
     *
     * @return bool
     */
    public function hasEmail()
    {
        return !empty($this->email);
    }
}