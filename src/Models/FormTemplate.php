<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_profile',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_profile' => 'boolean',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('step')->orderBy('sort_order');
    }

    /**
     * Fields grouped by step number.
     */
    public function fieldsByStep(): array
    {
        return $this->fields->groupBy('step')->toArray();
    }
}
