<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Outlet extends Model
{
    protected $fillable = ['code', 'name', 'address', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(OutletStock::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OutletOrder::class);
    }

    public function stocktakes(): HasMany
    {
        return $this->hasMany(OutletStocktake::class);
    }
}
