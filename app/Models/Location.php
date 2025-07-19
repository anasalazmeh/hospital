<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    /**
     * اسم الجدول المرتبط بالنموذج.
     *
     * @var string
     */
    protected $table = 'locations';

    /**
     * المفتاح الأساسي للجدول.
     *
     * @var string
     */

    /**
     * الحقول التي يمكن تعبئتها.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     * الحقول التي يجب إخفاؤها عند التحويل إلى JSON.
     *
     * @var array
     */

    /**
     * العلاقة مع جدول الأصناف (إذا كانت الأصناف مرتبطة بمواقع).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'location_id', 'location_id');
    }

    /**
     * نطاق البحث بالمواقع النشطة فقط.
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
            'id' => $this->location_id,
            'name' => $this->name,
            'description' => $this->description
        ];
    }

    /**
     * التحقق من وجود وصف للموقع.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return !empty($this->description);
    }
}