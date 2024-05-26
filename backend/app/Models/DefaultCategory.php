<?php

namespace App\Models;

use App\Enums\VcardTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'name'
    ];

    protected $casts = [
        'type' => VcardTypeEnum::class
    ];
}
