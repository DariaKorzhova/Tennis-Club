<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[А-Яа-яЁё]+$/u',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[А-Яа-яЁё]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
            'birth_date' => [
                'required',
                'date_format:d.m.Y',
                'before:today',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'first_name.regex' => 'Имя должно содержать только русские буквы.',
            'last_name.regex' => 'Фамилия должна содержать только русские буквы.',
            'email.regex' => 'Введите корректный email с доменом, например example@mail.com.',
            'birth_date.date_format' => 'Введите дату в формате ДД.ММ.ГГГГ.',
            'birth_date.before' => 'Дата рождения должна быть раньше сегодняшнего дня.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $firstName = $this->normalizeRussianName($request->first_name);
        $lastName = $this->normalizeRussianName($request->last_name);
        $birthDate = Carbon::createFromFormat('d.m.Y', $request->birth_date)->format('Y-m-d');

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => mb_strtolower(trim($request->email)),
            'birth_date' => $birthDate,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'specialization' => 'none',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        $this->startTwoFactorFlow($request, $user, false);

        return redirect()
            ->route('2fa.show')
            ->with('status', 'Код подтверждения отправлен на вашу почту. Завершите вход.');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $credentials = [
            'email' => mb_strtolower(trim($request->email)),
            'password' => $request->password,
        ];

        if (!Auth::validate($credentials)) {
            return back()
                ->withErrors(['email' => 'Неверные учетные данные'])
                ->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'Неверные учетные данные'])
                ->onlyInput('email');
        }
        

        /*if (!(bool) $user->two_factor_enabled) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return $this->redirectAfterAuth($user)
                ->with('success', 'Вход выполнен успешно!');
        }

        $this->startTwoFactorFlow($request, $user, $request->boolean('remember'));

        return redirect()
            ->route('2fa.show')
            ->with('status', 'Код отправлен на вашу почту.');*/

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectAfterAuth($user)
            ->with('success', 'Вход выполнен успешно!');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->forceFill([
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ])->save();
        }

        Auth::logout();

        $request->session()->forget([
            '2fa_user_id',
            '2fa_remember',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Вы вышли из системы');
    }

    private function startTwoFactorFlow(Request $request, User $user, bool $remember = false): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code' => $code,
            'two_factor_expires_at' => Carbon::now()->addMinutes(10),
        ])->save();

        $user->notify(new TwoFactorCodeNotification($code));

        Auth::logout();

        $request->session()->forget(['2fa_user_id', '2fa_remember']);
        $request->session()->put('2fa_user_id', $user->id);
        $request->session()->put('2fa_remember', $remember);
    }

    private function redirectAfterAuth(User $user)
    {
        if ($user->role === 'admin') {
            return redirect()->route('admin.users');
        }

        return redirect()->route('account');
    }

    private function normalizeRussianName(string $value): string
    {
        $value = trim(mb_strtolower($value));
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}