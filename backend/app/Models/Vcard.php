<?php

namespace App\Models;

use App\Http\Middleware\ValidateSignature;
use App\Services\Base64Services;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Vcard extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;
    protected $primaryKey = 'phone_number';
    public $incrementing = false;
    public $timestamps = true;
    protected $dates = ['deleted_at'];

    protected $attributes = [
        'blocked' => 0,
        'balance' => 0,
        'max_debit' => 5000,
        'custom_options' => '{"wantRoundedToPiggy": false, "wantNotifications": false}',
        'custom_data' => '{"piggyBankBalance": 0}'
    ];

    protected $fillable = [
        'phone_number',
        'name',
        'email',
        'photo_url',
        'password',
        'confirmation_code',
        'blocked',
        'balance',
        'max_debit'
    ];

    protected $hidden = [
        'password',
        'confirmation_code'
    ];

    protected $casts = [
        'password' => 'hashed',
        'confirmation_code' => 'hashed',
    ];

    public function categories()
    {
        return $this->hasMany(Category::class, 'vcard');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'vcard');
    }

    public function pair_transactions()
    {
        return $this->hasMany(Transaction::class, 'pair_vcard');
    }

    public function addDefaultCategoriesToVCard(): void
    {
        $defaultCategories = DefaultCategory::all()->whereNull('deleted_at');

        foreach ($defaultCategories as $defaultCategory) {
            $category = new Category();
            $category->vcard = $this->phone_number;
            $category->type = $defaultCategory->type;
            $category->name = $defaultCategory->name;
            $category->save();
        }
    }

    public function storeBase64AsFile(string $base64String)
    {
        $targetDir = storage_path('app/public/fotos');
        $newfilename = $this->phone_number . "_" . rand(1000, 9999);
        $base64Service = new Base64Services();
        return $base64Service->saveFile($base64String, $targetDir, $newfilename);
    }
}
