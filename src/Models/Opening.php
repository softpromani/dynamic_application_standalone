<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opening extends Model
{
    protected $table = 'openings';

    protected $fillable = ['program_id', 'subject_id', 'seats'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    // Keep 'job' as an alias for backward compatibility
    public function job(): BelongsTo
    {
        return $this->program();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(CustomEntityValue::class, 'subject_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
