<?php

namespace App\Http\Controllers;

use App\Models\FormTemplateM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormTemplateC extends Controller
{
    public function index()
    {
        $templates = FormTemplateM::with('creator')->get();
        return view('form_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('form_templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:form_templates,name',
            'description' => 'nullable|string',
            'show_row_totals' => 'boolean',
        ]);

        $validated['slug'] = str()->slug($validated['name']);
        $validated['created_by'] = Auth::id();
        $validated['is_active'] = $request->has('is_active');

        $template = FormTemplateM::create($validated);

        return redirect()->route('form_fields.edit', $template)->with('success', 'Şablon oluşturuldu. Şimdi lütfen bu formda olmasını istediğiniz alanları (Sütunları) ekleyin.');
    }

    public function show(FormTemplateM $formTemplate)
    {
        $formTemplate->load('fields');
        return view('form_templates.show', compact('formTemplate'));
    }

    public function destroy(Request $request, FormTemplateM $formTemplate)
    {
        $user = $request->user();
        if (!app(\App\Services\PermissionS::class)->hasPermission($user, 'form_templates.delete')) {
            abort(403, 'Form şablonu silme yetkiniz yok.');
        }

        try {
            $templateName = $formTemplate->name;
            $formTemplate->delete();
            
            \App\Services\NotificationS::dispatch('template.deleted', [
                'form_name' => $templateName,
            ]);

            return redirect()->route('form-templates.index')->with('success', 'Form şablonu başarıyla silindi.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Foreign key constraint failure (e.g. 1451 Cannot delete or update a parent row)
            if ($e->getCode() == 23000) {
                return redirect()->route('form-templates.index')->with('error', 'Bu şablona ait doldurulmuş form kayıtları (veriler) bulunmaktadır. Veri kaybını önlemek için şablon silinemez. Lütfen önce <a href="'.route('form-entries.index').'" class="underline font-bold">Veri Girişleri</a> sayfasından ilgili kayıtları silin veya şablonu "Pasif" duruma getirin.');
            }
            throw $e;
        }
    }
}
