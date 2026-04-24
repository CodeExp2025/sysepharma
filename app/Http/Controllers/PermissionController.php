<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Permission::query()
            ->when($search, fn ($q) => $q->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
            ->orderBy('name');

        return Inertia::render('Permissions/Index', [
            'permissions' => $query->get(),
            'filters'     => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('Permissions/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        Permission::create(['name' => $validated['name']]);

        return redirect()->route('permissions.index')->with('success', 'Permission créée avec succès.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Permission supprimée avec succès.');
    }

    public function edit(Permission $permission)
    {
        return Inertia::render('Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update(['name' => $validated['name']]);

        return redirect()->route('permissions.index')->with('success', 'Permission mise à jour avec succès.');
    }
}
