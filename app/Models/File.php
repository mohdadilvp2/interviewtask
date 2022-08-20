<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    const STATUS_NEW = 0;
    const STATUS_PROGRESS = 1;
    const STATUS_DONE = 2;
    const STATUS_ERROR = 3;

    protected $fillable = [
        'status',
        'contacts_file_path',
        'companies_file_path'
    ];
}
