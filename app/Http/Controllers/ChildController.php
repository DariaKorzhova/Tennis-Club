<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    public function create()
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            abort(403, 'доступ запрещён');
        }

        return view('account.children.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            abort(403, 'доступ запрещён');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female',
            'level'      => 'nullable|string|max:255',
            'notes'      => 'nullable|string',
        ]);

        $user->allChildren()->create($validated);

        return redirect()
            ->route('account')
            ->with('success', 'ребёнок успешно добавлен');
    }

    public function edit(Child $child)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            abort(403, 'доступ запрещён');
        }

        if ((int)$child->user_id !== (int)$user->id) {
            abort(403, 'доступ запрещён');
        }

        return view('account.children.edit', compact('child'));
    }

    public function update(Request $request, Child $child)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            abort(403, 'доступ запрещён');
        }

        if ((int)$child->user_id !== (int)$user->id) {
            abort(403, 'доступ запрещён');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female',
            'level'      => 'nullable|string|max:255',
            'notes'      => 'nullable|string',
        ]);

        $child->update($validated);

        return redirect()
            ->route('account')
            ->with('success', 'данные ребёнка обновлены');
    }
}