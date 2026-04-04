<?php

namespace App\Models;

use App\Traits\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockRequest extends Model
{
    use HasFactory, SoftDeletes, HasRouteUuid;

    protected $fillable = [
        'user_id',
        'depot_id',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    public function items()
    {
        return $this->hasMany(StockRequestItem::class);
    }
}
