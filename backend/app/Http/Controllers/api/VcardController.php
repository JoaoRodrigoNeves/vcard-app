<?php

namespace App\Http\Controllers\api;

use App\Enums\VcardTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\VCardMoveMoneyToPiggyBankRequest;
use App\Http\Requests\VCardRegisterRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VCardUserResource;
use App\Models\Category;
use App\Models\DefaultCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vcard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class VcardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $vcards = Vcard::query();

        if ($request->has('globalSearch')) {
            $searchableColumns = json_decode($request->input('searchable_columns'), true);

            $vcards->where(function ($query) use ($searchableColumns, $request) {
                foreach ($searchableColumns as $column) {
                    $query->orWhere($column, 'like', '%' . $request->input('globalSearch') . '%');
                }
            });
        }

        $vcardsCount = $vcards->count();

        if ($request->has('sortField') && $request->has('sortOrder')) {
            $vcards->orderBy($request->input('sortField'), $request->input('sortOrder'));
        }

        $first = $request->input('first', 0);
        $rows = $request->input('rows', 5);
        $vcards->skip($first)->take($rows);

        return response()->json([
            'message' => 'Success',
            'vCards' => VCardUserResource::collection($vcards->get()),
            'numberRows' => $vcardsCount
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
    public function show(Vcard $vcard)
    {
        return response()->json([
            'message' => "Success",
            'vCard' => $vcard
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, Vcard $vcard)
    {
        $data = $request->validated();
        $base64ImagePhoto = array_key_exists("base64ImagePhoto", $data) ?
            $data["base64ImagePhoto"] : ($data["base64ImagePhoto"] ?? null);
        $deletePhotoOnServer = array_key_exists("deletePhotoOnServer", $data) && $data["deletePhotoOnServer"];
        unset($data["base64ImagePhoto"]);
        unset($data["deletePhotoOnServer"]);

        // Delete previous photo file if a new file is uploaded or the photo is to be deleted
        if ($vcard->photo_url && ($deletePhotoOnServer || $base64ImagePhoto)) {
            if (Storage::exists('public/fotos/' . $vcard->photo_url)) {
                Storage::delete('public/fotos/' . $vcard->photo_url);
            }
            $vcard->photo_url = null;
        }
        // Create a new photo file from base64 content
        if ($base64ImagePhoto) {
            $vcard->photo_url = $vcard->storeBase64AsFile($base64ImagePhoto);
        }

        $vcard->name = $data["name"];
        $vcard->email = $data["email"];

        if (array_key_exists("password", $data) && $data["password"])
            $vcard->password = $data["password"];

        if (array_key_exists("confirmation_code", $data) && $data["confirmation_code"])
            $vcard->confirmation_code = $data["confirmation_code"];

        $vcard->save();
        return new UserResource(User::find($vcard->phone_number));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Vcard $vcard)
    {
        if (!$request->input('isAdmin')){
            if (!Hash::check($request['confirmationCode'], $vcard->confirmation_code)) {
                return response()->json([
                    'message' => "Código de confirmação errado",
                ], 401);
            }

            if (!Hash::check($request['password'], $vcard->password)) {
                return response()->json([
                    'message' => "Password errada",
                ], 401);
            }
        }

        $vCardPiggyBalance = json_decode($vcard->custom_data)->piggyBankBalance - 0;
        if ($vcard->balance + $vCardPiggyBalance > 0) {
            return response()->json([
                'message' => "Não é possível eliminar o vCard pois o saldo é superior a 0€",
            ], 401);
        }

        if ($vcard->transactions()->count() > 0) {
            $vcard->transactions()->delete();
            $vcard->categories()->delete();
            $vcard->delete();
            return response()->json([
                'message' => "Success",
                'data' => $vcard,
            ], 200);
        }

        if ($vcard->transactions()->count() === 0) {
            $vcard->categories()->forceDelete();
            $vcard->forceDelete();
            return response()->json([
                'message' => "Success",
                'data' => $vcard,
            ], 200);
        } else {
            return response()->json([
                'message' => "Success",
                'data' => $vcard,
            ], 200);
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

    public function addMoneyToPiggyBank(VCardMoveMoneyToPiggyBankRequest $request, Vcard $vcard)
    {
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

    public function removeMoneyFromPiggyBank(VCardMoveMoneyToPiggyBankRequest $request, Vcard $vcard)
    {
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

    public function changeVCardStatus(Vcard $vcard)
    {
        $vcard->blocked = !$vcard->blocked;
        $vcard->save();
        return response()->json([
            'message' => 'Success'
        ], 200);
    }

    public function wantRoundToPiggyBank(Vcard $vcard)
    {
        $customOptions = json_decode($vcard->custom_options);
        $customOptions->wantRoundedToPiggy = !$customOptions->wantRoundedToPiggy;
        $vcard->custom_options = json_encode($customOptions);
        $vcard->save();

        return response()->json([
            'message' => 'Success'
        ], 201);
    }

    public function updateVCardMaxDebit(Request $request, Vcard $vcard)
    {
        $vcard->max_debit = $request->input('value_max_debit');
        $vcard->save();
        return response()->json([
            'message' => 'O valor máximo de débito da conta ' . $request->input('phone_number') . ' foi alterado para ' . number_format($vcard->max_debit, 2) . '€'
        ], 200);
    }

    public function getCategories(Vcard $vcard)
    {
        return response()->json([
            'message' => "Success",
            'categories' => $vcard->categories()->orderBy('name')->get()->groupBy('type')
        ], 200);
    }

    public function getTransactionsCategories(Vcard $vcard)
    {
        $transactions = $vcard->transactions()->orderBy('id', 'desc')->get();
        $uniqueCategories = $transactions->pluck('category')->unique('id')->filter()->values()->all();
        return response()->json([
            'message' => "Success",
            'transactionsCategories' => $uniqueCategories
        ], 200);
    }

    public function getStatistics(Vcard $vcard)
    {
        $total_transactions = $vcard->transactions()->count();
        $money_spent_last_30_days = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('value')
            , 2);
        $money_spent_last_7_days = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('value')
            , 2);
        $money_spent_today = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->startOfDay())
                ->sum('value')
            , 2);

        for ($i = 1; $i < 13; $i++) {
            $money_spent_monthly[$i] = (float)number_format(
                $vcard->transactions()
                    ->where('type', VcardTypeEnum::DEBIT)
                    ->whereMonth('created_at', '=', $i)
                    ->sum('value')
                , 2);
        };
        $money_spent_weekly = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->subWeek())
                ->sum('value')
            , 2);

        $money_received_last_30_days = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('value')
            , 2);
        $money_received_last_7_days = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('value')
            , 2);
        $money_received_today = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->startOfDay())
                ->sum('value')
            , 2);
        $transactions_by_category = $vcard->transactions()
            ->selectRaw('category_id,
                SUM(CASE WHEN type = "D" THEN value ELSE 0 END) as total_debit,
                SUM(CASE WHEN type = "C" THEN value ELSE 0 END) as total_credit')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                $category = ($item->category_id !== null) ? strtoupper(Category::find($item->category_id)->name) : 'NO CATEGORY';
                return [
                    'category' => $category,
                    'total_debit' => (float)number_format($item->total_debit ,2, '.', ''),
                    'total_credit' => (float)number_format($item->total_credit , 2, '.', '')
                ];
            })
            ->sortByDesc('total_debit')
            ->values();

        for ($i = 1; $i < 13; $i++) {
            $money_received_monthly[$i] = (float)number_format(
                $vcard->transactions()
                    ->where('type', VcardTypeEnum::CREDIT)
                    ->whereMonth('created_at', '=', $i)
                    ->sum('value')
                , 2);
        };
        $money_received_weekly = (float)number_format(
            $vcard->transactions()
                ->where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->subWeek())
                ->sum('value')
            , 2);

        $statistics = [
            'total_transactions' => $total_transactions,
            'money_spent_last_30_days' => $money_spent_last_30_days,
            'money_spent_last_7_days' => $money_spent_last_7_days,
            'money_spent_today' => $money_spent_today,
            'money_spent_weekly' => $money_spent_weekly,
            'money_spent_monthly' => $money_spent_monthly,
            'money_received_last_30_days' => $money_received_last_30_days,
            'money_received_last_7_days' => $money_received_last_7_days,
            'money_received_today' => $money_received_today,
            'money_received_weekly' => $money_received_weekly,
            'money_received_monthly' => $money_received_monthly,
            //'transactions_spent_by_category' => $transactions_spent_by_category,
            //'transactions_received_by_category' => $transactions_received_by_category,
            'transactions_by_category' => $transactions_by_category,
        ];

        return response()->json([
            'message' => 'Success',
            'statistics' => $statistics
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

    public function getLast3Transactions(Vcard $vcard)
    {
        $transactions = $vcard->transactions()->orderBy('created_at', 'desc')->take(3)->get();
        return response()->json([
            'transactions' => TransactionResource::collection($transactions),
            'message' => 'Success'
        ], 200);
    }

    public function getTransactionsByCard(Request $request, Vcard $vcard)
    {
        switch ($request->input('orderBy')) {
            case ('highValue'):
                $transactions = $vcard->transactions()->selectRaw('* , (new_balance - old_balance) as balance_diff')
                    ->orderBy('balance_diff', 'ASC');
                break;
            case ('lowValue'):
                $transactions = $vcard->transactions()->selectRaw('* , (new_balance - old_balance) as balance_diff')
                    ->orderBy('balance_diff', 'DESC');
                break;
            case ('highDate'):
                $transactions = $vcard->transactions()->orderBy('datetime', 'ASC');
                break;
            case ('lowDate'):
                $transactions = $vcard->transactions()->orderBy('datetime', 'DESC');
                break;

            default:
                return response()->json([
                    'transactions' => [],
                    'message' => 'Success'
                ], 200);
        }

        if ($request->input('userPhoneNumberInput')) {
            $phoneNumberValue = $request->input('userPhoneNumberInput');
            $transactions = $transactions->where('payment_reference', 'like', "%$phoneNumberValue%");

            if ($transactions->count() == 0) {
                return response()->json([
                    'noTransactionsPhoneNumber' => 'Não tem transações para este número.',
                    'message' => 'Error'
                ], 200);
            }
        }
        if ($request->boolean('isCreditTransactions') && !$request->boolean('isDebitTransactions')) {
            $transactions = $transactions->where('type', VcardTypeEnum::CREDIT);

        } else if (!$request->boolean('isCreditTransactions') && $request->boolean('isDebitTransactions')) {
            $transactions = $transactions->where('type', VcardTypeEnum::DEBIT);
        } else if (!$request->boolean('isCreditTransactions') && !$request->boolean('isDebitTransactions')) {
            return response()->json([
                'transactions' => null,
                'message' => 'Success',
            ], 200);
        }

        if ($request->input('startDateFilter') && $request->input('endDateFilter')) {
            $transactions = $transactions->whereBetween('datetime', [Carbon::parse($request->input('startDateFilter'))->startOfDay(), Carbon::parse($request->input('endDateFilter'))->endOfDay()]);
        } else if ($request->input('startDateFilter') && !$request->input('endDateFilter')) {
            $transactions = $transactions->where('datetime', '>=', Carbon::parse($request->input('startDateFilter'))->startOfDay());
        } else if (!$request->input('startDateFilter') && $request->input('endDateFilter')) {
            $transactions = $transactions->where('datetime', '<=', Carbon::parse($request->input('endDateFilter'))->endOfDay());
        }
        if ($request->input('categories')) {
            $transactions = $transactions->whereIn('category_id', $request->input('categories'));
        }

        return response()->json([
            'transactions' => TransactionResource::collection($transactions->get()),
            'message' => 'Success',
        ], 200);
    }
}
