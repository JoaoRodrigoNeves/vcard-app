<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddAdminRequest;
use App\Http\Requests\AdminUpdatePasswordRequest;
use App\Mail\MailService;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vcard;
use App\Http\Requests\UpdateAdminRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function me()
    {
        $user = User::where('username', Auth::user()->username)->first();

        if($user->user_type == 'V'){
            $vcard = Vcard::where('phone_number', Auth::user()->id)->first();
            $custom_data = json_decode($vcard->custom_data);
            $custom_options = json_decode($vcard->custom_options);
            return (new UserResource(Auth::user()))->additional(["balance" => $vcard->balance, "piggyBankBalance" => $custom_data->piggyBankBalance, "wantRounded" => $custom_options->wantRoundedToPiggy]);
        }

        return (new UserResource(Auth::user()));
    }
}
