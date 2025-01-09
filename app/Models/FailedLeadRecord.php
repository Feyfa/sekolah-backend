<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedLeadRecord extends Model
{
    use HasFactory;

    protected $table = "failed_lead_records";

    protected $fillable = [
        'function',
        'type',
        'blocked_type',
        'leadspeek_api_id',
        'email_encrypt',
        'url',
        'leadspeek_type',
        'data_lead'
    ];
}
