<?php

namespace App\Http\Controllers;

use App\Models\CancellationRequest;
use App\Models\Training;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isTrainer()) {
            $trainings = Training::with(['rooms', 'trainer'])
                ->where('trainer_id', $user->id)
                ->orderBy('date')
                ->orderBy('time')
                ->get();

            $pendingIds = CancellationRequest::whereIn('training_id', $trainings->pluck('id'))
                ->where('status', 'pending')
                ->pluck('training_id')
                ->all();

            $trainings->each(function ($training) use ($pendingIds) {
                $training->has_pending_cancel = in_array($training->id, $pendingIds, true);
            });

            return view('account.index', compact('user', 'trainings'));
        }

        $trainings = $user->bookedTrainings()
            ->wherePivot('status', 'active')
            ->with(['rooms', 'trainer'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return view('account.index', compact('user', 'trainings'));
    }

    public function edit()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('account.edit', compact('user'));
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[А-Яа-яЁё]+$/u'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[А-Яа-яЁё]+$/u'],
            'birth_date' => ['required', 'date_format:d.m.Y', 'before:today'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'first_name.regex' => 'Имя должно содержать только русские буквы.',
            'last_name.regex' => 'Фамилия должна содержать только русские буквы.',
            'birth_date.date_format' => 'Дата рождения должна быть в формате ДД.ММ.ГГГГ.',
            'birth_date.before' => 'Дата рождения должна быть раньше сегодняшнего дня.',
            'photo.image' => 'Файл должен быть изображением.',
            'photo.mimes' => 'Фото должно быть в формате jpg, jpeg, png или webp.',
            'photo.max' => 'Размер фото не должен превышать 2 МБ.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->first_name = $this->normalizeRussianName($request->first_name);
        $user->last_name = $this->normalizeRussianName($request->last_name);
        $user->birth_date = Carbon::createFromFormat('d.m.Y', $request->birth_date)->format('Y-m-d');

        if ($request->hasFile('photo')) {
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            $user->photo = $request->file('photo')->store('profiles', 'public');
        }

        $user->save();

        return redirect()->route('account')->with('success', 'Данные аккаунта успешно обновлены.');
    }

    public function sendPasswordCode(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.required' => 'Введите новый пароль.',
            'new_password.min' => 'Пароль должен содержать минимум 8 символов.',
            'new_password.confirmed' => 'Пароли не совпадают.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        $user->notify(new TwoFactorCodeNotification($code));

        session([
            'password_change_user_id' => $user->id,
            'password_change_new_password' => $request->new_password,
        ]);

        return back()->with('status', 'Код подтверждения отправлен на вашу почту.');
    }

    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Введите код подтверждения.',
            'code.digits' => 'Код должен состоять из 6 цифр.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $sessionUserId = (int) session('password_change_user_id');
        $newPassword = session('password_change_new_password');

        if (!$sessionUserId || $sessionUserId !== (int) $user->id || !$newPassword) {
            return back()->withErrors([
                'code' => 'Сначала запросите код подтверждения для смены пароля.',
            ]);
        }

        if (!$user->two_factor_code || !$user->two_factor_expires_at) {
            return back()->withErrors([
                'code' => 'Код не найден. Запросите новый код.',
            ]);
        }

        if (Carbon::parse($user->two_factor_expires_at)->isPast()) {
            return back()->withErrors([
                'code' => 'Срок действия кода истёк. Запросите новый код.',
            ]);
        }

        if ((string) $request->code !== (string) $user->two_factor_code) {
            return back()->withErrors([
                'code' => 'Неверный код подтверждения.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        session()->forget([
            'password_change_user_id',
            'password_change_new_password',
        ]);

        return redirect()->route('account')->with('success', 'Пароль успешно изменён.');
    }

    private function normalizeRussianName(string $value): string
    {
        $value = trim(mb_strtolower($value));
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}