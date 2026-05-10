<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifyPhoneNumber extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'verification_code',
        'user_type',
    ];
}
