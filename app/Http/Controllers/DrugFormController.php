<?php

namespace App\Http\Controllers;

use App\Models\DrugForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DrugFormController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = DrugForm::query()
            ->when($search, fn ($q) => $q->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
            ->orderBy('name');

        return Inertia::render('DrugForms/Index', [
            'drugForms' => $query->paginate(10),
            'filters' => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('DrugForms/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:drug_forms,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DrugForm::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('drug-forms.index')->with('success', 'Forme pharmaceutique créée avec succès.');
    }

    public function edit(DrugForm $drugForm)
    {
        return Inertia::render('DrugForms/Edit', [
            'drugForm' => $drugForm,
        ]);
    }

    public function update(Request $request, DrugForm $drugForm)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:drug_forms,name,' . $drugForm->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $drugForm->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('drug-forms.index')->with('success', 'Forme pharmaceutique mise à jour avec succès.');
    }

    public function destroy(DrugForm $drugForm)
    {
        $drugForm->delete();
        return redirect()->route('drug-forms.index')->with('success', 'Forme pharmaceutique supprimée avec succès.');
    }

    public function list()
    {
        return response()->json([
            'forms' => DrugForm::active()->orderBy('name')->pluck('name'),
        ]);
    }
}
