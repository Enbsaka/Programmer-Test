<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['customer_id', 'total_amount', 'status'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function products()
    {
        // O withPivot avisa ao Laravel para trazer esses dados extras da tabela pivô
        return $this->belongsToMany(Product::class)
                    ->withPivot('quantity', 'unit_price');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}