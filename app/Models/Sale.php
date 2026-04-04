<?php

namespace App\Models;

use App\Traits\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory, HasRouteUuid;

    protected $fillable = ['transaction_id', 'depot_id', 'drug_unit_id', 'sold_by', 'sold_at', 'price', 'quantity'];

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    public function drugUnit()
    {
        return $this->belongsTo(DrugUnit::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }
}
