<?php

namespace App\Models;

use App\Traits\HasRouteUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Disbursement extends Model
{
    use HasRouteUuid;

    protected $fillable = ['uuid', 'initiated_by', 'notes', 'performed_at'];

    protected $casts = ['performed_at' => 'datetime'];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DisbursementItem::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->items->sum(fn ($item) => $item->quantite * $item->prix_unitaire);
    }
}
