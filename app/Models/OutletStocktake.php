<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutletStocktake extends Model
{
    protected $fillable = ['outlet_id', 'user_id', 'stocktake_date', 'notes'];

    protected $casts = ['stocktake_date' => 'date'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutletStocktakeItem::class);
    }
}
