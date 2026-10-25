<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'outlet_id',
        'outlet_order_item_id',
        'quantity',
        'from_location',
        'to_location',
        'status',
        'mutation_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'mutation_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outletOrderItem()
    {
        return $this->belongsTo(OutletOrderItem::class);
    }
}
