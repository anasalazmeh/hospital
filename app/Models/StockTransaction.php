<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quantity',
        'transaction_type',
        'notes',
        'item_id',
        'department_id',
        'location_id',
        'supplier_id',
        'user_id',
        'department_request_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */


    /**
     * Get the item associated with the transaction.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the department associated with the transaction.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who made the transaction.
     */
    public function user()
    {
        return $this->belongsTo(DashboardAccounts::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the location where the stock is stored.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function request()
    {
        return $this->belongsTo(DepartmentRequest::class, 'department_request_id');
    }
}