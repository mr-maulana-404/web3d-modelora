<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminTexture extends Model
{
    protected $fillable = [
        'name',
        'category',
        'texture_path',
    ];
}
