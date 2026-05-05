<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $userId = (int) $request->session()->get('2fa_user_id');
        $remember = (bool) $request->session()->get('2fa_remember', false);

        /** @var User|null $user */
        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['2fa_user_id', '2fa_remember']);

            return redirect()->route('login');
        }

        if (!$user->two_factor_code || !$user->two_factor_expires_at) {
            return redirect()->back()->withErrors([
                'code' => 'код не найден. нажмите "отправить ещё раз".',
            ]);
        }

        if (Carbon::parse($user->two_factor_expires_at)->isPast()) {
            return redirect()->back()->withErrors([
                'code' => 'код истёк. нажмите "отправить ещё раз".',
            ]);
        }

        if ((string) $request->code !== (string) $user->two_factor_code) {
            return redirect()->back()->withErrors([
                'code' => 'неверный код.',
            ]);
        }

        $user->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget(['2fa_user_id', '2fa_remember']);

        if ($user->role === 'admin') {
            return redirect()->route('admin.users')->with('success', 'вход выполнен успешно!');
        }

        return redirect()->route('account')->with('success', 'вход выполнен успешно!');
    }

    public function resend(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $userId = (int) $request->session()->get('2fa_user_id');

        /** @var User|null $user */
        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['2fa_user_id', '2fa_remember']);

            return redirect()->route('login');
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code' => $code,
            'two_factor_expires_at' => Carbon::now()->addMinutes(10),
        ])->save();

        $user->notify(new TwoFactorCodeNotification($code));

        return redirect()->route('2fa.show')->with('status', 'новый код отправлен на почту.');
    }
}