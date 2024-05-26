<?php

namespace App\Models;

use App\Enums\VcardTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'vcard',
        'type',
        'name'
    ];

    protected $casts = [
        'type' => VcardTypeEnum::class
    ];

    public function vcard()
    {
        return $this->belongsTo(Vcard::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }
}
