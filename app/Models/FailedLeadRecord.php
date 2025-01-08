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
        'campaign_id',
        'md5_email',
        'url',
        'module_type'
    ];
}
