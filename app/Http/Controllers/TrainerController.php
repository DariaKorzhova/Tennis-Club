<?php

namespace App\Http\Controllers;

use App\Models\User;

class TrainerController extends Controller
{
    public function show(User $trainer)
    {
        if (!$trainer->isTrainer()) {
            abort(404);
        }

        return view('trainers.show', compact('trainer'));
    }
}
