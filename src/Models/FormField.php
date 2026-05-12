<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $fillable = [
        'form_template_id',
        'step',
        'sort_order',
        'field_type',
        'label',
        'name',
        'placeholder',
        'options',
        'is_required',
        'system_alias',
        'custom_entity_id',
    ];

    protected $casts = [
        'is_required'      => 'boolean',
        'options'          => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ApplicationResponse::class);
    }

    public function customEntity(): BelongsTo
    {
        return $this->belongsTo(CustomEntity::class);
    }
}
