<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    use HasFactory;

    // Status column values
    const STATUS_NEW = 0;
    const STATUS_PROGRESS = 1;
    const STATUS_DONE = 2;
    const STATUS_ERROR = 3;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'contacts_file_path',
        'companies_file_path'
    ];
}
