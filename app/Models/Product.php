<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'supplier_id',
        'unit',
        'stock',
        'minimum_stock',
        'description',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'integer',
        'minimum_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function stockMutations()
    {
        return $this->hasMany(StockMutation::class);
    }

    public function outletStocks()
    {
        return $this->hasMany(OutletStock::class);
    }

    public function outletOrderItems()
    {
        return $this->hasMany(OutletOrderItem::class);
    }

    public function outletStocktakeItems()
    {
        return $this->hasMany(OutletStocktakeItem::class);
    }
}
