<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'quantity',
        'location_id',
        'supplier_id',
        'manufacturing_date',
        'expiry_date',
        'batch_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the item associated with the stock.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
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

    /**
     * Get all transactions for this stock item.
     */
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'item_id', 'item_id');
    }

}