<?php

namespace App\Models;

use App\Traits\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasRouteUuid;

    protected $fillable = ['name', 'description', 'created_by'];

    public function drugs()
    {
        return $this->hasMany(Drug::class);
    }
}
