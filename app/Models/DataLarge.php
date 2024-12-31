<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataLarge extends Model
{
    use HasFactory;

    protected $table = 'data_larges';

    protected $fillable = [
        'data1',
        'data2',
        'data3',
    ];
}
