<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomEntityValue extends Model
{
    protected $fillable = ['custom_entity_id', 'value', 'label', 'sort_order'];
    protected $appends = ['name', 'code'];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(CustomEntity::class, 'custom_entity_id');
    }

    public function getNameAttribute()
    {
        return $this->label;
    }

    public function getCodeAttribute()
    {
        return $this->value;
    }
}
