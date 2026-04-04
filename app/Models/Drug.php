<?php

namespace App\Models;

use App\Traits\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use HasFactory, SoftDeletes, HasRouteUuid;

    protected $fillable = [
        'category_id',
        'name',
        'effet_s_med',
        'dosage_med',
        'form_med',
        'prix_med',
        'is_authorized',
        'description',
        'created_by',
        'min_stock',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function drugUnits()
    {
        return $this->hasMany(DrugUnit::class);
    }
}
