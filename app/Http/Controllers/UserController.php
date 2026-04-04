<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $auth         = $request->user();
        $isSuperAdmin = $auth->hasRole('super_admin');

        $search    = $request->query('search');
        $sort      = $request->query('sort', 'name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['name', 'email', 'created_at'], true)) {
            $sort = 'name';
        }

        $query = User::with(['roles', 'pharmacy', 'depot']);

        // pharmacy_admin sees only users of their pharmacy
        if (! $isSuperAdmin && $auth->pharmacy_id) {
            $query->where('pharmacy_id', $auth->pharmacy_id);
        }

        if ($search) {
            $query->where(fn ($q) => $q->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"])
                                       ->orWhereRaw('email COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]));
        }

        $query->orderBy($sort, $direction);

        return Inertia::render('Users/Index', [
            'users'   => $query->paginate(10),
            'filters' => [
                'search'    => $search,
                'sort'      => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $auth         = $request->user();
        $isSuperAdmin = $auth->hasRole('super_admin');

        // Roles: pharmacy_admin cannot create super_admin accounts
        $roles = $isSuperAdmin
            ? Role::all()
            : Role::whereNotIn('name', ['super_admin'])->get();

        // Pharmacies: scoped to user's own pharmacy for non-super_admin
        $pharmacies = $isSuperAdmin
            ? Pharmacy::all()
            : Pharmacy::whereHas('users', fn ($q) => $q->where('users.id', $auth->id))->get();

        // Depots: scoped to user's pharmacy's depots
        $depots = $isSuperAdmin
            ? Depot::with('pharmacy')->get()
            : Depot::with('pharmacy')->where('pharmacy_id', $auth->pharmacy_id)->get();

        return Inertia::render('Users/Create', [
            'roles'      => $roles,
            'pharmacies' => $pharmacies,
            'depots'     => $depots,
        ]);
    }

    public function store(Request $request)
    {
        $auth         = $request->user();
        $isSuperAdmin = $auth->hasRole('super_admin');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|max:255|unique:users',
            'password'    => 'required|string|min:8',
            'role'        => 'required|string|exists:roles,name',
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            'depot_id'    => 'nullable|exists:depots,id',
        ]);

        // Prevent non-super_admin from assigning super_admin role
        if (! $isSuperAdmin && $validated['role'] === 'super_admin') {
            abort(403, 'Vous ne pouvez pas attribuer le rôle super_admin.');
        }

        // pharmacy_admin can only create users for their own pharmacy
        if (! $isSuperAdmin && $auth->pharmacy_id) {
            $validated['pharmacy_id'] = $auth->pharmacy_id;
        }

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'pharmacy_id'       => $validated['pharmacy_id'] ?? null,
            'depot_id'          => $validated['depot_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        // Attach user to the pharmacy pivot table if pharmacy_id is set
        if ($user->pharmacy_id) {
            $user->pharmacies()->syncWithoutDetaching([$user->pharmacy_id]);
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(Request $request, User $user)
    {
        if ($user->hasRole('super_admin')) {
            abort(403, 'Le compte super_admin ne peut pas être modifié.');
        }

        $auth         = $request->user();
        $isSuperAdmin = $auth->hasRole('super_admin');

        $user->load(['roles', 'pharmacy', 'depot']);

        $roles = $isSuperAdmin
            ? Role::all()
            : Role::whereNotIn('name', ['super_admin'])->get();

        $pharmacies = $isSuperAdmin
            ? Pharmacy::all()
            : Pharmacy::whereHas('users', fn ($q) => $q->where('users.id', $auth->id))->get();

        $depots = $isSuperAdmin
            ? Depot::with('pharmacy')->get()
            : Depot::with('pharmacy')->where('pharmacy_id', $auth->pharmacy_id)->get();

        return Inertia::render('Users/Edit', [
            'user'        => $user,
            'roles'       => $roles,
            'pharmacies'  => $pharmacies,
            'depots'      => $depots,
            'currentRole' => $user->roles->first()?->name,
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole('super_admin')) {
            abort(403, 'Le compte super_admin ne peut pas être modifié.');
        }

        $auth         = $request->user();
        $isSuperAdmin = $auth->hasRole('super_admin');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role'        => 'required|string|exists:roles,name',
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            'depot_id'    => 'nullable|exists:depots,id',
        ]);

        if (! $isSuperAdmin && $validated['role'] === 'super_admin') {
            abort(403, 'Vous ne pouvez pas attribuer le rôle super_admin.');
        }

        if (! $isSuperAdmin && $auth->pharmacy_id) {
            $validated['pharmacy_id'] = $auth->pharmacy_id;
        }

        $user->update([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'pharmacy_id' => $validated['pharmacy_id'] ?? $user->pharmacy_id,
            'depot_id'    => $validated['depot_id'] ?? null,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('super_admin')) {
            abort(403, 'Le compte super_admin ne peut pas être supprimé.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
