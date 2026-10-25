<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletStocktakeItem extends Model
{
    protected $fillable = [
        'outlet_stocktake_id', 'product_id', 'opening_stock',
        'received_quantity', 'closing_stock', 'used_quantity', 'discrepancy',
    ];

    protected $casts = [
        'opening_stock' => 'integer',
        'received_quantity' => 'integer',
        'closing_stock' => 'integer',
        'used_quantity' => 'integer',
        'discrepancy' => 'integer',
    ];

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(OutletStocktake::class, 'outlet_stocktake_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
