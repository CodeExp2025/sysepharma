<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Drug;
use App\Models\DrugForm;
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
        $search    = $request->query('search');
        $sort      = $request->query('sort', 'name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'prix_med', 'form_med', 'created_at', 'category_name'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $query = Drug::with('category')
            ->when($search, fn ($q) => $q->whereRaw('drugs.name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"])
                                         ->orWhereRaw('form_med COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"])
                                         ->orWhereRaw('dosage_med COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]));

        if ($sort === 'category_name') {
            $query->leftJoin('categories', 'drugs.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $direction)
                  ->select('drugs.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $drugs = $query->paginate(10);

        return Inertia::render('Drugs/Index', [
            'drugs'   => $drugs,
            'filters' => [
                'search'    => $search,
                'sort'      => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Drugs/Create', [
            'categories' => Category::all(),
            'drugForms' => DrugForm::active()->orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Build readable attribute names per drug row: "nom du médicament #1", etc.
        $drugAttributes = [];
        foreach (array_keys($request->drugs ?? []) as $i) {
            $n = $i + 1;
            $drugAttributes["drugs.{$i}.name"]        = "nom du médicament #{$n}";
            $drugAttributes["drugs.{$i}.prix_med"]    = "prix du médicament #{$n}";
            $drugAttributes["drugs.{$i}.form_med"]    = "forme pharmaceutique du médicament #{$n}";
            $drugAttributes["drugs.{$i}.dosage_med"]  = "dosage du médicament #{$n}";
            $drugAttributes["drugs.{$i}.effet_s_med"] = "effet secondaire du médicament #{$n}";
        }

        $request->validate([
            'category_id'          => 'required|exists:categories,id',
            'drugs'                => 'required|array|min:1',
            'drugs.*.name'         => 'required|string|max:255',
            'drugs.*.effet_s_med'  => 'nullable|string|max:255',
            'drugs.*.dosage_med'   => 'nullable|string|max:255',
            'drugs.*.form_med'     => 'nullable|string|max:255',
            'drugs.*.prix_med'     => 'required|numeric|min:0',
            'drugs.*.description'  => 'nullable|string',
        ], [], $drugAttributes);

        $categoryId = $request->category_id;
        $userId     = Auth::id();

        foreach ($request->drugs as $drug) {
            Drug::create([
                'category_id' => $categoryId,
                'name'        => $drug['name'],
                'effet_s_med' => $drug['effet_s_med'] ?? null,
                'dosage_med'  => $drug['dosage_med'] ?? null,
                'form_med'    => $drug['form_med'] ?? null,
                'prix_med'    => $drug['prix_med'],
                'description' => $drug['description'] ?? null,
                'created_by'  => $userId,
            ]);
        }

        $count = count($request->drugs);
        $msg   = $count > 1 ? "{$count} médicaments ajoutés avec succès." : 'Médicament ajouté avec succès.';

        return redirect()->route('drugs.index')->with('success', $msg);
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
            'drugForms' => DrugForm::active()->orderBy('name')->pluck('name'),
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
