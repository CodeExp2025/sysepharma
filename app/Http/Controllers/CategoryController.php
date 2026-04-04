<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search    = $request->query('search');
        $sort      = $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['name', 'created_at'], true)) {
            $sort = 'created_at';
        }

        $query = Category::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                                         ->orWhere('description', 'like', "%{$search}%"))
            ->orderBy($sort, $direction);

        return Inertia::render('Categories/Index', [
            'categories' => $query->paginate(10),
            'filters'    => [
                'search'    => $search,
                'sort'      => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Categories/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        Category::create($validated);

        return redirect()->back()->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->back()->with('success', 'Catégorie supprimée avec succès.');
    }
}
