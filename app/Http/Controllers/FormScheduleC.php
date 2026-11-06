<?php

namespace App\Http\Controllers;

use App\Models\FormScheduleM;
use App\Models\FormTemplateM;
use App\Models\GroupM;
use App\Models\UserM;
use Illuminate\Http\Request;

class FormScheduleC extends Controller
{
    public function index(FormTemplateM $formTemplate)
    {
        $schedules = FormScheduleM::where('form_template_id', $formTemplate->id)->latest()->get();
        $groups = GroupM::where('is_active', true)->get();
        $users = UserM::where('is_active', true)->get();

        return view('form_schedules.index', compact('formTemplate', 'schedules', 'groups', 'users'));
    }

    public function store(Request $request, FormTemplateM $formTemplate)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:all,group,user',
            'target_id' => 'nullable|integer',
            'is_indefinite' => 'boolean',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'is_locked' => 'boolean'
        ]);

        $validated['form_template_id'] = $formTemplate->id;
        $validated['is_indefinite'] = $request->has('is_indefinite');
        $validated['is_locked'] = $request->has('is_locked');

        FormScheduleM::create($validated);

        return redirect()->route('form-schedules.index', $formTemplate)->with('success', 'Erişim planı başarıyla eklendi.');
    }

    public function destroy(FormScheduleM $formSchedule)
    {
        $templateId = $formSchedule->form_template_id;
        $formSchedule->delete();
        
        return redirect()->route('form-schedules.index', $templateId)->with('success', 'Erişim planı silindi.');
    }
}
