<?php

namespace App\Http\Controllers\apiTAES;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\VCardMoveMoneyToPiggyBankRequest;
use App\Http\Requests\VCardRegisterRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VCardUserResource;
use App\Models\Category;
use App\Models\DefaultCategory;
use App\Models\User;
use App\Models\Vcard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class VcardControllerTAES extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vCards = Vcard::all();
        return response()->json([
            'message' => "Success",
            'vCards' => VCardUserResource::collection($vCards)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VCardRegisterRequest $request)
    {
        $data = $request->validated();
        $vCard = Vcard::where('phone_number', $request->only('username'))->first();
        if ($vCard) {
            return response()->json([
                'message' => "O número de telémovel introduzido já se encontra utilizado.",
            ], 401);
        }

        $base64ImagePhoto = array_key_exists("base64ImagePhoto", $data) ?
            $data["base64ImagePhoto"] : ($data["base64ImagePhoto"] ?? null);

        $vcard = new Vcard($request->validated());
        if ($base64ImagePhoto) {
            $vcard->photo_url = $vcard->storeBase64AsFile($base64ImagePhoto);
        }

        $vcard->custom_data = json_encode([
            'piggyBankBalance' => 0,
            'expoTokens' => [$request->input('expo_push_token')]
        ]);

        $vcard->phone_number = $data["username"];
        $vcard->save();
        $vcard->addDefaultCategoriesToVCard();

        if ($vcard->name == "TAES" && $vcard->email == "taes@taes.pt") {
            return response()->json([
                'message' => "Success",
                'phoneNumber' => $vcard->phone_number
            ], 200);
        }
        return response()->json([
            'message' => "Success",
            'vCard' => $vcard
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $vcard = Vcard::where('phone_number', $id)->first();

        if (!$vcard) {
            return response()->json([
                'message' => 'Não existe nenhum VCard associado a este contacto.'
            ], 401);
        }

        return response()->json([
            'message' => "Success",
            'vCard' => $vcard
        ], 200);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vCard = Vcard::where('phone_number', $id)->first();
        $vCardPiggyBalance = json_decode($vCard->custom_data)->piggyBankBalance - 0;

        if ($vCard->balance + $vCardPiggyBalance > 0) {
            return response()->json([
                'message' => "Error",
            ], 401);
        }

        if ($vCard->transactions()->count() > 0) {
            $vCard->transactions()->delete();
            $vCard->categories()->delete();
            $vCard->delete();
            return response()->json([
                'message' => "Success"
            ], 200);
        }

        if ($vCard->transactions()->count() === 0) {
            $vCard->categories()->forceDelete();
            $vCard->forceDelete();
            return response()->json([
                'message' => "Success"
            ], 200);
        } else {
            return response()->json([
                'message' => "Error",
            ], 401);
        }
    }

    public function authenticate(Request $request)
    {

        // APP CONFIRMATION CODE
        if ($request->input('password') === null) {
            $vcard = Vcard::where('phone_number', $request->only('phone_number'))->first();
            $custom_data = json_decode($vcard->custom_data);
            $vcard->custom_data = json_encode([
                'piggyBankBalance' => $custom_data->piggyBankBalance,
                'notifications' => $custom_data->notifications ?? [],
                'expoTokens' => [$request->input('expo_push_token')]
            ]);
            $vcard->save();
            if (Hash::check($request['confirmation_code'], $vcard->confirmation_code)) {
                return response()->json([
                    'vcard' => $vcard,
                    'message' => 'Success'
                ], 200);
            }
        }

        // DEFAULT_LOGIN
        if (Auth::guard('vCard')->attempt($request->only('phone_number', 'password'))) {
            $vcard = Auth::guard('vCard')->user();
            $custom_data = json_decode($vcard->custom_data);
            $vcard->custom_data = json_encode([
                'piggyBankBalance' => $custom_data->piggyBankBalance,
                'notifications' => $custom_data->notifications ?? [],
                'expoTokens' => [$request->input('expo_push_token')]
            ]);
            $vcard->save();
            return response()->json([
                'vcard' => $vcard,
                'message' => 'Success'
            ], 200);
        }
        return response()->json([
            'message' => 'Credênciais inválidas'
        ], 401);
    }

    public function addMoneyToPiggyBank(VCardMoveMoneyToPiggyBankRequest $request, string $id)
    {
        $vcard = Vcard::where('phone_number', $id)->first();
        $custom_data = json_decode($vcard->custom_data);

        if ($vcard->balance < $request->input('value')) {
            return response()->json([
                'message' => 'Não possui dinheiro suficiente na sua conta.'
            ], 401);
        }

        $custom_data->piggyBankBalance += $request->input('value');
        $vcard->balance -= $request->input('value');
        $vcard->custom_data = json_encode([
            'piggyBankBalance' => $custom_data->piggyBankBalance,
            'notifications' => $custom_data->notifications ?? [],
            'expoTokens' => $custom_data->expoTokens ?? []
        ]);
        $vcard->save();

        return response()->json([
            'message' => "Success",
        ], 200);
    }

    public function removeMoneyFromPiggyBank(VCardMoveMoneyToPiggyBankRequest $request, string $id)
    {
        $vcard = Vcard::where('phone_number', $id)->first();
        $custom_data = json_decode($vcard->custom_data);

        if ($request->input('value') <= $custom_data->piggyBankBalance) {
            $custom_data->piggyBankBalance -= $request->input('value');

            $vcard->custom_data = json_encode([
                'piggyBankBalance' => $custom_data->piggyBankBalance,
                'notifications' => $custom_data->notifications ?? [],
                'expoTokens' => $custom_data->expoTokens ?? []
            ]);
            $vcard->balance += $request->input('value');
            $vcard->save();
        } else {
            return response()->json([
                'message' => 'Não possui dinheiro suficiente na sua conta.'
            ], 401);
        }
        return response()->json([
            'message' => 'Success'
        ], 200);
    }

    public function changeNotificationsStatus(string $id)
    {
        $vcard = Vcard::where('phone_number', $id)->first();
        if (!$vcard) {
            return response()->json([
                'message' => 'VCard não encontrado.'
            ], 401);
        }

        $customOptions = json_decode($vcard->custom_options);
        $customOptions->wantNotifications = !$customOptions->wantNotifications;
        $vcard->custom_options = json_encode($customOptions);
        $vcard->save();

        return response()->json([
            'message' => 'Success'
        ], 200);
    }

    public function changeVCardStatus(string $id)
    {
        $vcard = Vcard::where('phone_number', $id)->first();
        if (!$vcard) {
            return response()->json([
                'message' => 'Não existe nenhum VCard associado a este contacto.'
            ], 401);
        }

        $vcard->blocked = !$vcard->blocked;
        $vcard->save();

        return response()->json([
            'message' => 'Success'
        ], 200);
    }

    public function wantRoundToPiggyBank(string $id)
    {
        $vcard = Vcard::where('phone_number', $id)->first();
        if (!$vcard) {
            return response()->json([
                'message' => 'VCard não encontrado.'
            ], 401);
        }

        $customOptions = json_decode($vcard->custom_options);
        $customOptions->wantRoundedToPiggy = !$customOptions->wantRoundedToPiggy;
        $vcard->custom_options = json_encode($customOptions);
        $vcard->save();

        return response()->json([
            'message' => 'Success'
        ], 200);
    }

    public function getTransactions(string $id) {
        $transactions = Vcard::find($id)->transactions()->orderBy('id', 'desc')->get();
        $uniqueCategories = $transactions->pluck('category')->unique('id')->filter()->values()->all();

        return response()->json([
            'message' => "Success",
            'transactions' => TransactionResource::collection($transactions),
            'transactionsCategories' => $uniqueCategories
        ], 200);
    }

    public function getNotifications(string $id)
    {
        $get_vcard = Vcard::where('phone_number', $id)->first();
        if (!$get_vcard) {
            return response()->json([
                'message' => 'Não existe nenhum vCard associado a este contacto.'
            ], 401);
        }
        $custom_data = json_decode($get_vcard->custom_data);
        $response = array_reverse($custom_data->notifications) ?? [];
        return response()->json([
            'message' => 'Success',
            'notifications' => $response
        ], 200);
    }

    public function updateNotifications(string $id, string $notification_id)
    {
        $get_vcard = Vcard::where('phone_number', $id)->first();
        if (!$get_vcard) {
            return response()->json([
                'message' => 'Não existe nenhum vCard associado a este contacto.'
            ], 401);
        }
        $custom_data = json_decode($get_vcard->custom_data);
        $custom_data->notifications[$notification_id - 1]->read = true;
        $get_vcard->custom_data = json_encode($custom_data);
        $get_vcard->save();
        return response()->json([
            'message' => 'Success'
        ], 200);
    }
}
