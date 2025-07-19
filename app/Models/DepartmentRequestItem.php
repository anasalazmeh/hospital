<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentRequestItem extends Model
{
    protected $fillable = [
        'department_request_id',
        'item_id',
        'location_id',
        'quantity',
        'approved_quantity',
        'delivered_quantity',
        'batch_number'
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DepartmentRequest::class, 'department_request_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
    public function departmentRequest()
    {
        return $this->belongsTo(DepartmentRequest::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}