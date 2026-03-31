<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Drug;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DrugController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Drug::with('category');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $drugs = $query->latest()->paginate(10);

        return Inertia::render('Drugs/Index', [
            'drugs' => $drugs,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Drugs/Create', [
            'categories' => Category::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'effet_s_med' => 'nullable|string|max:255',
            'dosage_med' => 'nullable|string|max:255',
            'form_med' => 'nullable|string|max:255',
            'prix_med' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        Drug::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('drugs.index')->with('success', 'Drug created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Drug $drug)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Drug $drug)
    {
        return Inertia::render('Drugs/Edit', [
            'drug' => $drug,
            'categories' => Category::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Drug $drug)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'effet_s_med' => 'nullable|string|max:255',
            'dosage_med' => 'nullable|string|max:255',
            'form_med' => 'nullable|string|max:255',
            'prix_med' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $drug->update($validated);

        return redirect()->route('drugs.index')->with('success', 'Drug updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Drug $drug)
    {
        $drug->delete();
        return redirect()->route('drugs.index')->with('success', 'Drug deleted successfully.');
    }
}
