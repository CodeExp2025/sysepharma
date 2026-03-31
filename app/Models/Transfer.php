<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_pharmacy_id',
        'to_depot_id',
        'performed_by',
        'status',
        'items_count',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function depot()
    {
        return $this->belongsTo(Depot::class, 'to_depot_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }
}
