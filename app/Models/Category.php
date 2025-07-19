<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean' // لضمان أن الحالة تُحفظ كـ true/false
    ];
    // علاقة مع جدول المواد (تصنيف يمكن أن يحتوي على العديد من المواد)
    public function Item()
    {
        return $this->hasMany(Item::class);
    }

}
