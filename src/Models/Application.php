<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'application_no',
        'applicant_id',
        'opening_id',
        'program_application_type_id',
        'status',
        'action_status',
        'current_step',
        'form_status',
        'fee_amount',
        'tax_amount',
        'fine_amount',
        'total_amount',
        'submitted_at',
        'responses',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'responses' => 'array',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function opening(): BelongsTo
    {
        return $this->belongsTo(Opening::class);
    }

    public function applicationType(): BelongsTo
    {
        return $this->belongsTo(ProgramApplicationType::class, 'program_application_type_id');
    }

    public function eavResponses(): HasMany
    {
        return $this->hasMany(ApplicationResponse::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    /**
     * Synchronize the application's payment status based on its transactions.
     */
    public function syncPaymentStatus()
    {
        // If the job is marked as free, it's always considered 'paid'
        if ($this->opening?->job && !$this->opening->job->is_payable) {
            $this->update(['status' => 'paid']);
            return 'paid';
        }

        $hasSuccess = $this->transactions()
            ->whereIn('status', ['success', 'completed'])
            ->exists();
            
        if ($hasSuccess) {
            $this->update(['status' => 'paid']);
            return 'paid';
        }
        
        $hasPending = $this->transactions()
            ->whereIn('status', ['initiated', 'pending', 'processing'])
            ->exists();
            
        if ($hasPending) {
            $this->update(['status' => 'pending']);
            return 'pending';
        }
        
        $hasFailed = $this->transactions()
            ->where('status', 'failed')
            ->exists();
            
        if ($hasFailed) {
            $this->update(['status' => 'failed']);
            return 'failed';
        }

        return $this->status;
    }
}
