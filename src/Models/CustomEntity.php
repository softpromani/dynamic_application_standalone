<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomEntity extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    public function values(): HasMany
    {
        return $this->hasMany(CustomEntityValue::class)->orderBy('sort_order');
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }
}
