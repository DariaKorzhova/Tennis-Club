<?php

namespace App\Http\Controllers;

use App\Models\CancellationRequest;
use App\Models\CourtBooking;
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

            return view('account.index', [
                'user' => $user,
                'trainings' => $trainings,
                'courtBookings' => collect(),
            ]);
        }

        $trainings = $user->bookedTrainings()
            ->wherePivot('status', 'active')
            ->with(['rooms', 'trainer'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $courtBookings = $user->courtBookings()
            ->with('room')
            ->where('status', 'active')
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->groupBy(function ($booking) {
                return $booking->booking_group ?: $booking->id;
            })
            ->map(function ($group) {
                $first = $group->sortBy('time')->first();
                $first->hours_count = $group->count();
                $first->total_price = $group->sum('price');
                return $first;
            })
            ->values();

        return view('account.index', compact('user', 'trainings', 'courtBookings'));
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
            'birth_date' => ['required', 'date', 'before:today'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'crop_x' => ['nullable', 'numeric', 'min:0'],
            'crop_y' => ['nullable', 'numeric', 'min:0'],
            'crop_size' => ['nullable', 'numeric', 'min:10'],
        ], [
            'first_name.regex' => 'Имя должно содержать только русские буквы.',
            'last_name.regex' => 'Фамилия должна содержать только русские буквы.',
            'birth_date.date' => 'Укажите корректную дату рождения.',
            'birth_date.before' => 'Дата рождения должна быть раньше сегодняшнего дня.',
            'photo.image' => 'Файл должен быть изображением.',
            'photo.mimes' => 'Фото должно быть в формате jpg, jpeg, png или webp.',
            'photo.max' => 'Размер фото не должен превышать 4 МБ.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->first_name = $this->normalizeRussianName($request->first_name);
        $user->last_name = $this->normalizeRussianName($request->last_name);
        $user->birth_date = Carbon::parse($request->birth_date)->format('Y-m-d');

        if ($request->hasFile('photo')) {
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            $path = $this->storeCroppedProfilePhoto(
                $request->file('photo'),
                $request->input('crop_x'),
                $request->input('crop_y'),
                $request->input('crop_size')
            );

            $user->photo = $path;
        }

        $user->save();

        return redirect()->route('account')->with('success', 'Данные аккаунта успешно обновлены.');
    }

    public function cancelCourtBooking($group)
    {
        /** @var User $user */
        $user = Auth::user();

        $bookings = CourtBooking::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) use ($group) {
                $q->where('booking_group', $group)->orWhere('id', $group);
            })
            ->get();

        if ($bookings->isEmpty()) {
            return back()->with('error', 'Аренда не найдена.');
        }

        foreach ($bookings as $booking) {
            $booking->status = 'cancelled';
            $booking->save();
        }

        return back()->with('success', 'Аренда корта отменена.');
    }

    public function updateCourtBookingPersons(Request $request, $group)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'persons' => ['required', 'integer', 'min:1', 'max:4'],
        ], [
            'persons.required' => 'Укажите количество человек.',
            'persons.integer' => 'Количество человек должно быть числом.',
            'persons.min' => 'Минимум 1 человек.',
            'persons.max' => 'Максимум 4 человека.',
        ]);

        $bookings = CourtBooking::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) use ($group) {
                $q->where('booking_group', $group)->orWhere('id', $group);
            })
            ->get();

        if ($bookings->isEmpty()) {
            return back()->with('error', 'Аренда не найдена.');
        }

        foreach ($bookings as $booking) {
            $booking->persons = (int) $request->persons;
            $booking->save();
        }

        return back()->with('success', 'Количество человек обновлено.');
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

    private function storeCroppedProfilePhoto($file, $cropX = null, $cropY = null, $cropSize = null): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $tmpPath = $file->getRealPath();

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($tmpPath);
                break;
            case 'png':
                $source = imagecreatefrompng($tmpPath);
                break;
            case 'webp':
                $source = imagecreatefromwebp($tmpPath);
                break;
            default:
                return $file->store('profiles', 'public');
        }

        if (!$source) {
            return $file->store('profiles', 'public');
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $cropX = is_numeric($cropX) ? (int) $cropX : 0;
        $cropY = is_numeric($cropY) ? (int) $cropY : 0;
        $cropSize = is_numeric($cropSize) ? (int) $cropSize : min($srcWidth, $srcHeight);

        if ($cropSize < 10) {
            $cropSize = min($srcWidth, $srcHeight);
        }

        if ($cropX + $cropSize > $srcWidth) {
            $cropX = max(0, $srcWidth - $cropSize);
        }

        if ($cropY + $cropSize > $srcHeight) {
            $cropY = max(0, $srcHeight - $cropSize);
        }

        $finalSize = 600;
        $result = imagecreatetruecolor($finalSize, $finalSize);

        imagecopyresampled(
            $result,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $finalSize,
            $finalSize,
            $cropSize,
            $cropSize
        );

        $dir = storage_path('app/public/profiles');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $fileName = 'profiles/' . uniqid('profile_', true) . '.jpg';
        $fullPath = storage_path('app/public/' . $fileName);

        imagejpeg($result, $fullPath, 90);

        imagedestroy($source);
        imagedestroy($result);

        return $fileName;
    }
}