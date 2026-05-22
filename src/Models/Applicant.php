<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
class Applicant extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, MustVerifyEmailTrait;

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


}
