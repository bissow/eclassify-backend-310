<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberOtp extends Model
{
    use HasFactory;
    protected $table = 'number_otps';

    protected $fillable = [
        'number',
        'otp',
        'session_id',
        'expire_at',
        'attempts'
    ];

    // Removed base64 encoding/decoding mutators
    // OTP is stored as bcrypt hash (for Twilio) or null (for 2Factor which uses session_id)
    // Base64 encoding interferes with bcrypt hashing
}
