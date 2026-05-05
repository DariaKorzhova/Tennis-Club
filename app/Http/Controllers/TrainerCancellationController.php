<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\CancellationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerCancellationController extends Controller
{
    public function requestCancel(Request $request, Training $training)
    {
        $user = Auth::user();

        if (!$user || !$user->isTrainer()) {
            return redirect()->back()->with('error', 'доступ только для тренера.');
        }

        // тренер может запросить отмену только своей тренировки
        if ((int)$training->trainer_id !== (int)$user->id) {
            return redirect()->back()->with('error', 'вы можете запросить отмену только своей тренировки.');
        }

        if ((bool)$training->is_cancelled) {
            return redirect()->back()->with('error', 'тренировка уже отменена.');
        }

        $exists = CancellationRequest::where('training_id', $training->id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'запрос на отмену уже отправлен.');
        }

        CancellationRequest::create([
            'training_id' => $training->id,
            'trainer_id'  => $user->id,
            'status'      => 'pending',
            'reason'      => $request->input('reason'),
        ]);

        return redirect()->back()->with('success', 'запрос на отмену отправлен админу.');
    }
}
