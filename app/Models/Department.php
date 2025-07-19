<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status'
    ];
    protected $casts = [
        'status' => 'boolean' // لضمان أن الحالة تُحفظ كـ true/false
    ];
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'department_id');
    }
}
