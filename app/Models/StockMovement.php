<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = ['drug_unit_id', 'transfer_id', 'from_type', 'from_id', 'to_type', 'to_id', 'action', 'performed_by', 'performed_at'];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function drugUnit()
    {
        return $this->belongsTo(DrugUnit::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
