<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'notes',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}