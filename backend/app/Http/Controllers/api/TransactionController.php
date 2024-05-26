<?php

namespace App\Http\Controllers\api;

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

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = all();
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
            if ($request->input('payment_type') === 'VCARD') {
                $paymentReferenceVcardCheck = Vcard::where('phone_number', $request->only('payment_reference'))->first();
                if (!$paymentReferenceVcardCheck) {
                    return response()->json([
                        'message' => "Número de telemóvel inválido",
                    ], 401);
                }

                if ($paymentReferenceVcardCheck->blocked) {
                    return response()->json([
                        'message' => "Não é possivel transferir para este vcard. O vcard está bloqueado",
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

                if ($request['confirmationCode'] != null) {
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
                $transactionD->description = $request->description;
                $transactionD->old_balance = $vCardSource->balance;
                $transactionD->new_balance = $vCardSource->balance - $request->value;
                $transactionD->pair_vcard = $transactionD->payment_reference;
                if ($request->input('category')) {
                    $transactionD->category_id = $request->input('category');
                }
                $transactionD->save();

                $transactionC = new Transaction($request->validated());
                $transactionC->date = $date;
                $transactionC->datetime = $datetime;
                $transactionC->type = 'C';
                $transactionC->description = $request->description;
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
                }

                return response()->json([
                    'message' => "Success",
                    'data' => $transactionD,
                ], 201); // Created
            } else {
                if ($request->input('value') <= 0 || $request->input('value') >= 100000) {
                    return response()->json([
                        'message' => "Valor inválido o valor deve ser entre 0 e 100000€",
                    ], 401);
                }
                if ($request->input('payment_type') === 'MBWAY' && $request->input('value') > 50) {
                    return response()->json([
                        'message' => "Valor máximo para transferências MBWAY é de 50€",
                    ], 401);
                }
                if ($request->input('payment_type') === 'PAYPAL' && $request->input('value') > 100) {
                    return response()->json([
                        'message' => "Valor máximo para transferências PAYPAL é de 100€",
                    ], 401);
                }
                if ($request->input('payment_type') === 'IBAN' && $request->input('value') > 1000) {
                    return response()->json([
                        'message' => "Valor máximo para transferências IBAN é de 1000€",
                    ], 401);
                }
                if ($request->input('payment_type') === 'MB' && $request->input('value') > 500) {
                    return response()->json([
                        'message' => "Valor máximo para transferências MB é de 500€",
                    ], 401);
                }
                if ($request->input('payment_type') === 'VISA' && $request->input('value') > 200) {
                    return response()->json([
                        'message' => "Valor máximo para transferências VISA é de 200€",
                    ], 401);
                }

                $vcard = Vcard::where('phone_number', $request->only('vcard'))->first();
                if ($request->input('type') == 'D' && $request->input('value') > $vcard->max_debit){
                    return response()->json([
                        'message' => "Não é possivel fazer uma transação com este valor. O valor máximo é " . $vcard->max_debit . " €",
                    ], 401);
                }
                if ($vcard->blocked) {
                    return response()->json([
                        'message' => "Não é possivel transferir para este vcard. O vcard está bloqueado",
                    ], 401);
                }
                $payload = [
                    'type' => $request->input('payment_type'),
                    'reference' => str_replace(' ', '', $request->input('payment_reference')),
                    'value' => $request->input('value'),
                ];

                $response = Http::post('https://dad-202324-payments-api.vercel.app/api/debit', $payload);

                if ($response->status() === 201) {


                    $date = date('Y-m-d');
                    $datetime = date('Y-m-d H:i:s');

                    $transaction = new Transaction();
                    $transaction->vcard = $request->input('vcard');
                    $transaction->date = $date;
                    $transaction->datetime = $datetime;
                    $transaction->type = $request->input('type');
                    $transaction->value = $request->input('value');
                    $transaction->old_balance = $vcard->balance;
                    if ($request->input('type') == 'D') {
                        $transaction->new_balance = $vcard->balance - $request->input('value');
                        $vcard->balance -= $request->input('value');
                    } else {
                        $transaction->new_balance = $vcard->balance + $request->input('value');
                        $vcard->balance += $request->input('value');
                    }
                    $transaction->payment_type = $request->input('payment_type');
                    $transaction->payment_reference = $request->input('payment_reference');
                    $transaction->save();
                    $vcard->save();
                    return response()->json([
                        'message' => 'Transação criada com sucesso.',
                        'data' => $transaction
                    ], 201);
                } else {
                    return response()->json(['error' => 'Não foi possivel criar a transação'], $response->status());
                }
            }
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
    public function update(Request $request, Transaction $transaction)
    {
        if ($request->input('isUpdatingCategory')) {
            $transaction->category_id = $request->input('categoryId');
        } else {
            $transaction->description = $request->input('description');
        }
        $transaction->save();
        return response()->json(['message' => 'Success'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //mudar o deleted_at
    }

    public function getTransactionsByDateFilter(Request $request)
    {
        $transactions = Transaction::query();

        $transactions->where(function ($query) {
            $query->where(function ($innerQuery) {
                $innerQuery->where('type', 'D')->where('payment_type', 'VCARD');
            })->orWhere(function ($innerQuery) {
                $innerQuery->where('payment_type', '!=', 'VCARD');
            });
        });

        if ($request->has('transactionsFilter.startDate') && $request->has('transactionsFilter.endDate')) {
            $transactions->whereBetween('datetime', [
                Carbon::parse($request->input('transactionsFilter.startDate'))->startOfDay(),
                Carbon::parse($request->input('transactionsFilter.endDate'))->endOfDay()
            ]);
        } else if ($request->has('transactionsFilter.startDate') && !$request->has('transactionsFilter.endDate')) {
            $transactions->where('datetime', '>=', Carbon::parse($request->input('transactionsFilter.startDate'))->startOfDay());
        } else if (!$request->has('transactionsFilter.startDate') && $request->has('transactionsFilter.endDate')) {
            $transactions->where('datetime', '<=', Carbon::parse($request->input('transactionsFilter.endDate'))->endOfDay());
        }

        if ($request->has('globalSearch')) {
            $searchTerm = $request->input('globalSearch');
            $searchableColumns = json_decode($request->input('searchable_columns'), true);

            if (is_array($searchableColumns) && count($searchableColumns) > 0) {
                $transactions->where(function ($query) use ($searchableColumns, $searchTerm) {
                    foreach ($searchableColumns as $column) {
                        $query->orWhere($column, 'like', '%' . $searchTerm . '%');
                    }
                });
            }
        }

        $transactionsCount = $transactions->count();

        if ($request->has('sortField') && $request->has('sortOrder')) {
            $transactions->orderBy($request->input('sortField'), $request->input('sortOrder'));
        }

        $first = $request->input('first', 0);
        $rows = $request->input('rows', 5);
        $transactions->skip($first)->take($rows);

        return response()->json([
            'message' => 'Success',
            'transactions' => TransactionResource::collection($transactions->get()),
            'numberRows' => $transactionsCount
        ], 200);
    }


}
