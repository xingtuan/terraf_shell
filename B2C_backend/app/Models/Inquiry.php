<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'country',
        'inquiry_type',
        'message',
        'source_page',
        'status',
    ];
}
