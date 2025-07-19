<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /**
     * اسم الجدول المرتبط بالنموذج.
     *
     * @var string
     */
    protected $table = 'items';

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
        'category_id',
        'description',
        'unit',
        'min_stock',
        'max_stock',
        'barcode'
    ];

    /**
     * الحقول التي يجب إخفاؤها عند التحويل إلى JSON.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /**
     * العلاقة مع جدول التصنيفات.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function departmentRequestItems()
    {
        return $this->hasMany(DepartmentRequestItem::class);
    }
    /**
     * العلاقة مع جدول الحركات (إذا كان موجوداً).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'item_id', 'item_id');
    }

    /**
     * نطاق البحث بالأصناف النشطة فقط.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق البحث بالأصناف التي تحت حد الطلب.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBelowMinStock($query)
    {
        return $query->whereColumn('quantity', '<', 'min_stock');
    }

    /**
     * وصول سريع لاسم التصنيف.
     *
     * @return string
     */
    public function getCategoryNameAttribute()
    {
        return $this->category->name ?? 'غير مصنف';
    }

    /**
     * التحقق من أن الكمية أقل من الحد الأدنى.
     *
     * @return bool
     */
    public function isBelowMinStock()
    {
        return $this->quantity < $this->min_stock;
    }
}