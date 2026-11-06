<?php

namespace App\Http\Controllers;

use App\Models\GroupM;
use Illuminate\Http\Request;

class GroupC extends Controller
{
    public function index()
    {
        $groups = GroupM::withCount('users')->get();
        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:groups,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');
        GroupM::create($validated);

        return redirect()->route('groups.index')->with('success', 'Grup başarıyla oluşturuldu.');
    }

    public function edit(GroupM $group)
    {
        return view('groups.edit', compact('group'));
    }

    public function update(Request $request, GroupM $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:groups,name,' . $group->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');
        $group->update($validated);

        return redirect()->route('groups.index')->with('success', 'Grup başarıyla güncellendi.');
    }
}
