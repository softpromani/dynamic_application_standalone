<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Applicant extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'dob',
        'gender',
        'category',
        'father_name',
        'mother_name',
        'marital_status',
        'permanent_address',
        'correspondence_address',
        'id_proof_type',
        'id_proof_number',
        'profile_photo_path',
        'signature_path',
        'profile_data',
        'is_profile_complete',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'dob' => 'date',
        'profile_data' => 'array',
        'is_profile_complete' => 'boolean',
    ];

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        // Verification disabled
    }
}
