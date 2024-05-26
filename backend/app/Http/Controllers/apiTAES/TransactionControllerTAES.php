<?php

namespace App\Http\Controllers\apiTAES;

use App\Enums\VcardTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Mail\MailService;
use App\Models\Vcard;
use App\Notifications\NewSampleNotification;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionControllerTAES extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::all();
        //return $vCardUsers;
        return response()->json([
            'message' => "Success",
            'transactions' => TransactionResource::collection($transactions)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            $paymentReferenceVcardCheck = Vcard::where('phone_number', $request->only('payment_reference'))->first();
            if (!$paymentReferenceVcardCheck) {
                return response()->json([
                    'message' => "Número de telemóvel inválido",
                ], 401);
            }

            if ($request->input('vcard') == $request->input('payment_reference')) {
                return response()->json([
                    'message' => "Não pode transferir para o seu vcard",
                ], 401);
            }

            $vCardSource = Vcard::where('phone_number', $request->only('vcard'))->first();
            if ($vCardSource->balance < $request->value) {
                return response()->json([
                    'message' => "Saldo Insuficiente",
                ], 401);
            }

            if ($vCardSource->max_debit < $request->value) {
                return response()->json([
                    'message' => "Não é possivel fazer uma transação com este valor. O valor máximo é " . $vCardSource->max_debit . " €",
                ], 401);
            }

            if($request['confirmationCode'] != null){
                if (!Hash::check($request['confirmationCode'], $vCardSource->confirmation_code)) {
                    return response()->json([
                        'message' => "Código de confirmação errado",
                    ], 401);
                }
            }

            $date = date('Y-m-d');
            $datetime = date('Y-m-d H:i:s');

            $transactionD = new Transaction($request->validated());
            $transactionD->date = $date;
            $transactionD->datetime = $datetime;
            $transactionD->type = 'D';
            $transactionD->old_balance = $vCardSource->balance;
            $transactionD->new_balance = $vCardSource->balance - $request->value;
            $transactionD->pair_vcard = $transactionD->payment_reference;
            if ($request->input('category')){
                $transactionD->category_id = $request->input('category');
            }
            $transactionD->save();

            $transactionC = new Transaction($request->validated());
            $transactionC->date = $date;
            $transactionC->datetime = $datetime;
            $transactionC->type = 'C';
            $transactionC->old_balance = $paymentReferenceVcardCheck->balance;
            $transactionC->new_balance = $paymentReferenceVcardCheck->balance + $request->value;
            $transactionC->vcard = $transactionD->payment_reference;
            $transactionC->payment_reference = $transactionD->vcard;
            $transactionC->pair_transaction = $transactionD->id;
            $transactionC->pair_vcard = $transactionD->vcard;
            $transactionC->save();

            $transactionD->pair_transaction = $transactionC->id;
            $transactionD->save();
            $vCardSource->balance -= $request->value;
            $paymentReferenceVcardCheck->balance += $request->value;
            $paymentReferenceVcardCheck->save();
            $vCardSource->save();
            $reference_vcard_custom_data = json_decode($paymentReferenceVcardCheck->custom_data);
            $reference_vcard_custom_options = json_decode($paymentReferenceVcardCheck->custom_options);

            $notification = [
                "id" => isset($reference_vcard_custom_data->notifications) ? count($reference_vcard_custom_data->notifications) + 1 : 1,
                "title" => "Transferência",
                "message" => "Transferência recebida de " . $request->vcard . " com o valor " . $request->value . "€",
                "datetime" => $datetime,
                "to" => $request->payment_reference,
                "from" => $request->vcard,
                "value" => $request->value,
                "read" => false,
            ];
            isset($reference_vcard_custom_data->notifications) ? array_push($reference_vcard_custom_data->notifications, $notification) : $reference_vcard_custom_data->notifications = [$notification];

            if (isset($reference_vcard_custom_options->wantNotifications) &&
                ($reference_vcard_custom_options->wantNotifications) &&
                isset($reference_vcard_custom_data->expoTokens) &&
                !is_null($reference_vcard_custom_data->expoTokens[0]))
            {
                $expoMessage = new NewSampleNotification($notification);
                $paymentReferenceVcardCheck->notify($expoMessage);
            }
            $paymentReferenceVcardCheck->custom_data = json_encode([
                'expoTokens' => $reference_vcard_custom_data->expoTokens ?? [],
                'notifications' => $reference_vcard_custom_data->notifications,
                'piggyBankBalance' => $reference_vcard_custom_data->piggyBankBalance
            ]);
            $paymentReferenceVcardCheck->save();

            if (json_decode($vCardSource->custom_options)->wantRoundedToPiggy && floor($request->value) != $request->value) {
                $roundedPiggyValue = number_format(ceil($request->value - 0) - $request->value, 2, '.', '');

                if ($roundedPiggyValue > $vCardSource->balance) {
                    return response()->json([
                        'message' => "Não possui saldo suficiente para efetuar o arredondamento. Mas a transação foi efetuada com sucesso.",
                    ], 401);
                }

                $roundedPiggyValue = number_format(ceil($request->value - 0) - $request->value, 2, '.', '');
                $custom_data = json_decode($vCardSource->custom_data);
                $custom_data->piggyBankBalance += ($roundedPiggyValue - 0);

                $vCardSource->custom_data = json_encode([
                    'expoTokens' => $custom_data->expoTokens ?? [],
                    'notifications' => $custom_data->notifications ?? [],
                    'piggyBankBalance' => $custom_data->piggyBankBalance
                ]);

                $vCardSource->balance -= $roundedPiggyValue;
                $vCardSource->save();

                return response()->json([
                    'message' => "Success",
                    'moneySaved' => $roundedPiggyValue
                ], 201); // Created
            }

            return response()->json([
                'message' => "Success",
            ], 201); // Created
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating transaction',
                'error' => $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile(),
            ], 500); // Error
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //mudar o deleted_at
    }

    public function getTransactionsByCardLast3(string $id)
    {
        $transactions = Transaction::where('vcard', $id)->orderBy('created_at', 'desc')->take(3)->get();
        return response()->json([
            'transactions' => $transactions,
            'message' => 'Success'
        ], 200);
    }

    // TAES
    public function getTransactionsByCard(Request $request, string $id)
    {
        switch ($request->input('orderBy')) {
            case ('highValue'):
                $transactions = Transaction::selectRaw('* , (new_balance - old_balance) as balance_diff')
                    ->where('vcard', $id)
                    ->orderBy('balance_diff', 'ASC');
                break;
            case ('lowValue'):
                $transactions = Transaction::selectRaw('* , (new_balance - old_balance) as balance_diff')
                    ->where('vcard', $id)
                    ->orderBy('balance_diff', 'DESC');
                break;
            case ('highDate'):
                $transactions = Transaction::where('vcard', $id)->orderBy('datetime', 'ASC');
                break;
            case ('lowDate'):
                $transactions = Transaction::where('vcard', $id)->orderBy('datetime', 'DESC');
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
        if($request->input('categories')){
            $transactions = $transactions->whereIn('category_id', $request->input('categories'));
            return $transactions->get();
        }

        return response()->json([
            'transactions' => $transactions->get(),
            'message' => 'Success',
        ], 200);
    }

    public function createExternalTransaction(Request $request)
    {
        $payload = [
            'type' => $request->input('creditTransaction.type'),
            'reference' => $request->input('creditTransaction.reference'),
            'value' => $request->input('creditTransaction.value'),
        ];

        $response = Http::post('https://dad-202324-payments-api.vercel.app/api/debit', $payload);

        if ($response->status() === 201) {
            $vcard = Vcard::where('phone_number', $request->only('userPhoneNumber'))->first();

            $date = date('Y-m-d');
            $datetime = date('Y-m-d H:i:s');

            $transaction = new Transaction();
            $transaction->vcard = $request->input('userPhoneNumber');
            $transaction->date = $date;
            $transaction->datetime = $datetime;
            $transaction->type = $request->input('transactionType');
            $transaction->value = $request->input('creditTransaction.value');
            $transaction->old_balance = $vcard->balance;
            $transaction->new_balance = $vcard->balance + $request->input('creditTransaction.value');
            $transaction->payment_type = $request->input('creditTransaction.type');
            $transaction->payment_reference = $request->input('creditTransaction.reference');
            $transaction->save();

            if ($request->input('transactionType') == 'D') {
                $vcard->balance -= $request->input('creditTransaction.value');
            } else {
                $vcard->balance += $request->input('creditTransaction.value');
            }
            $vcard->save();
            return response()->json([
                'message' => 'Transação criada com sucesso.',
            ], 201);
        } else {
            return response()->json(['error' => 'Não foi possivel criar a transação'], $response->status());
        }
    }

    public function getTransactionsByDateFilter(Request $request)
    {
        $transactions = Transaction::where('deleted_at', null);

        if ($request->has('startDate') && $request->has('endDate')) {
            $transactions = $transactions->whereBetween('datetime', [Carbon::parse($request->input('startDate'))->startOfDay(), Carbon::parse($request->input('endDate'))->endOfDay()]);
        } else if ($request->has('startDate') && !$request->has('endDate')) {
            $transactions = $transactions->where('datetime', '>=', Carbon::parse($request->input('startDate'))->startOfDay());
        } else if (!$request->has('startDate') && $request->has('endDate')) {
            $transactions = $transactions->where('datetime', '<=', Carbon::parse($request->input('endDate'))->endOfDay());
        }

        return response()->json([
            'message' => "Success",
            'transactions' => TransactionResource::collection($transactions->get())
        ], 200);
    }


}
