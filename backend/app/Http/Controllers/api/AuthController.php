<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{

    public function login(Request $request)
    {
        $passportData = [
            'grant_type' => 'password',
            'client_id' => env('PASSPORT_PASSWORD_GRANT_ID'),
            'client_secret' => env('PASSPORT_PASSWORD_GRANT_SECRET'),
            'username' => $request->username,
            'password' => $request->password,
            'scope' => '',
        ];

        $username = $request->username;

        request()->request->add($passportData);

        $request = Request::create(env('PASSPORT_SERVER_URL') . '/oauth/token', 'POST');
        $response = Route::dispatch($request);
        $errorCode = $response->getStatusCode();

        $user = User::where('username', $username)->first();
        if ($errorCode == '200' && $user && !$user->blocked) {
            return json_decode((string)$response->content(), true);
        } else {
            return response()->json(['message' => 'User credentials are invalid'], 400);
        }
    }

    public function logout(Request $request)
    {
        $accessToken = $request->user()->token();
        $token = $request->user()->tokens->find($accessToken);
        $token->revoke();
        $token->delete();
        return response(['message' => 'Token revoked',], 200);
    }
}
