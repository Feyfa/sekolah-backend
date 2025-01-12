<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadProgress extends Model
{
    use HasFactory;

    protected $table = 'download_progress';

    protected $fillable = [
        'user_id',
        'name',
        'link',
        'status',
    ];
}
