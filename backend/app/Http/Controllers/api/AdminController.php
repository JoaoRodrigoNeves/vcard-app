<?php

namespace App\Http\Controllers\api;

use App\Enums\VcardTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddAdminRequest;
use App\Http\Requests\AdminUpdatePasswordRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Http\Resources\AdminUsersResource;
use App\Http\Resources\UserResource;
use App\Mail\MailService;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class AdminController extends Controller
{
    public function index()
    {
        $vCardUsers = Admin::all();
        //return $vCardUsers;
        return response()->json([
            'message' => "Success",
            'admins' => AdminUsersResource::collection($vCardUsers)
        ], 200);
    }

    public function store(AddAdminRequest $request)
    {
        $emailToken = Str::random(32);

        $checkEmail = Admin::where('email', $request->only('email'))->first();

        if($checkEmail){
            return response()->json([
                'message' => 'Já existe um administrador com o email inserido.'
            ], 401);
        }

        $user = new Admin($request->validated());
        $user->password = bcrypt('8wLq1YojyE7+/e');
        $user->custom_data = json_encode([
            'setPasswordToken' => $emailToken
        ]);

        $user->save();
        Mail::to('ricardo.p.gomes@ipleiria.pt')
            ->bcc('pedrorolo@live.com.pt')
            ->send(new MailService($emailToken, $user->name));

        return response()->json([
            'userAdmin' => $user,
            'message' => 'Administrador criado com sucesso.'
        ], 200);
    }

    public function update(UpdateAdminRequest $request, Admin $admin)
    {
        $data = $request->validated();
        $admin->fill($data);
        $admin->save();
        return new UserResource($admin);
    }

    public function destroy(Admin $admin)
    {
        $admin->forceDelete();
        return response()->json([
            'message' => 'O administrador foi apagado com sucesso.'
        ], 200);
    }

    public function updateAdminPasswordByToken(AdminUpdatePasswordRequest $request)
    {
        $user = Admin::where('custom_data->setPasswordToken', $request->only('token'))->first();
        if(!$user){
            return response()->json([
                'message' => 'Não foi possivel atualizar a password. Token inválido.'
            ], 401);
        }

        $user->password = $request->password;
        $user->custom_data = null;
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        return response()->json([
            'message' => $user
        ], 200);
    }

    public function getStats()
    {
        $total_transactions = Transaction::count();

        $money_spent_last_30_days =
            Transaction::where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('value');

        $money_spent_last_7_days =
            Transaction::where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('value');

        $money_spent_today =
            Transaction::where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->startOfDay())
                ->sum('value');


        for ($i = 1; $i < 13; $i++) {
            $money_spent_monthly[$i] =
                Transaction::where('type', VcardTypeEnum::DEBIT)
                    ->whereMonth('created_at', '=', $i)
                    ->sum('value');
        };
        $money_spent_weekly =
            Transaction::where('type', VcardTypeEnum::DEBIT)
                ->where('created_at', '>=', now()->subWeek())
                ->sum('value');


        $money_received_last_30_days =
            Transaction::where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('value');

        $money_received_last_7_days =
            Transaction::where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('value');

        $money_received_today =
            Transaction::where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->startOfDay())
                ->sum('value');

        $transactions_by_payment_type = Transaction::select('payment_type')
            ->selectRaw('count(*) as total')
            ->groupBy('payment_type')
            ->get();

        for ($i = 1; $i < 13; $i++) {
            $money_received_monthly[$i] =
                Transaction::where('type', VcardTypeEnum::CREDIT)
                    ->whereMonth('created_at', '=', $i)
                    ->sum('value');
        };
        $money_received_weekly =
            Transaction::where('type', VcardTypeEnum::CREDIT)
                ->where('created_at', '>=', now()->subWeek())
                ->sum('value');


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
            'transactions_by_payment_type' => $transactions_by_payment_type,
        ];

        return response()->json([
            'message' => 'Success',
            'statistics' => $statistics
        ], 200);
    }
}
