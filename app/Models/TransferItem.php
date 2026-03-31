<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'drug_unit_id',
        'quantity',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function drugUnit()
    {
        return $this->belongsTo(DrugUnit::class);
    }
}

