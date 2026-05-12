<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramApplicationType extends Model
{
    protected $table = 'program_application_types';

    protected $fillable = ['program_id', 'name', 'fee', 'fine_amount'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
