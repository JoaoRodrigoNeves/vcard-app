<?php

namespace App\Models;

use App\Enums\VcardTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        'vcard',
        'date',
        'datetime',
        'type',
        'value',
        'old_balance',
        'new_balance',
        'payment_type',
        'payment_reference',
        'name'
    ];

    protected $nullable = [
        'pair_transaction',
        'pair_vcard',
        'category_id'
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
        return $this->hasMany(Transaction::class);
    }

    public function pair_transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }
}
