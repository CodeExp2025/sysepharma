<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrugUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['drug_id', 'price', 'barcode', 'quantite_contenu', 'quantite_actuelle', 'expiration_date', 'status', 'current_location_type', 'current_location_id', 'created_by'];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function sales()
    {
        return $this->hasOne(Sale::class);
    }
}
