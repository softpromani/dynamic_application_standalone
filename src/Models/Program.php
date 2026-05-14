<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Program extends Model
{
    protected $table = 'programs';

    protected $fillable = [
        'job_code',
        'title',
        'description',
        'application_start_date',
        'application_end_date',
        'last_payment_date',
        'application_fee',
        'fine_amount',
        'tax_percentage',
        'is_active',
        'form_template_id',
        'footer_notes',
        'is_payable',
        'custom_entity_id',
        'preview_config'
    ];

    protected $casts = [
        'application_start_date' => 'date',
        'application_end_date' => 'date',
        'last_payment_date' => 'date',
        'is_active' => 'boolean',
        'is_payable' => 'boolean',
        'preview_config' => 'array',
    ];

    public function applicationTypes(): HasMany
    {
        return $this->hasMany(ProgramApplicationType::class);
    }

    public function openings(): HasMany
    {
        return $this->hasMany(Opening::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function customEntity(): BelongsTo
    {
        return $this->belongsTo(CustomEntity::class);
    }

    public function applications()
    {
        return $this->hasManyThrough(Application::class, Opening::class);
    }
}
