<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementItem extends Model
{
    protected $fillable = ['disbursement_id', 'designation', 'quantite', 'prix_unitaire'];

    protected $casts = [
        'quantite'      => 'float',
        'prix_unitaire' => 'float',
    ];

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
    }

    public function getMontantAttribute(): float
    {
        return $this->quantite * $this->prix_unitaire;
    }
}
