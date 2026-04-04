<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Pharmacy;
use App\Models\User;
use App\Notifications\DepotLowStockNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DepotController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $search    = $request->query('search');
        $sort      = $request->query('sort', 'name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['name', 'address', 'created_at'], true)) {
            $sort = 'name';
        }

        $query = Depot::with('pharmacy')
            ->when($search, fn ($q) => $q->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"])
                                         ->orWhereRaw('address COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
            ->orderBy($sort, $direction);

        if (! $isSuperAdmin && $user->pharmacy_id) {
            $query->where('pharmacy_id', $user->pharmacy_id);
        }

        return Inertia::render('Depots/Index', [
            'depots'  => $query->paginate(10),
            'filters' => [
                'search'    => $search,
                'sort'      => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $pharmacies = $isSuperAdmin
            ? Pharmacy::all()
            : Pharmacy::whereHas('users', fn ($q) => $q->where('users.id', $user->id))->get();

        return Inertia::render('Depots/Create', [
            'pharmacies' => $pharmacies,
        ]);
    }

    public function store(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'address'     => 'nullable|string|max:255',
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        // pharmacy_admin can only create depots for their own pharmacy
        if (! $isSuperAdmin && $user->pharmacy_id && (int) $validated['pharmacy_id'] !== (int) $user->pharmacy_id) {
            abort(403, 'Vous ne pouvez créer des dépôts que pour votre pharmacie.');
        }

        Depot::create($validated);

        return redirect()->route('depots.index')->with('success', 'Dépôt créé avec succès.');
    }

    public function show(Depot $depot)
    {
        $depot->load('pharmacy');
        return Inertia::render('Depots/Show', [
            'depot' => $depot,
        ]);
    }

    public function edit(Request $request, Depot $depot)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        // Only admin of this depot's pharmacy can edit it
        if (! $isSuperAdmin && $user->pharmacy_id && $depot->pharmacy_id !== $user->pharmacy_id) {
            abort(403);
        }

        $pharmacies = $isSuperAdmin
            ? Pharmacy::all()
            : Pharmacy::whereHas('users', fn ($q) => $q->where('users.id', $user->id))->get();

        return Inertia::render('Depots/Create', [
            'depot'      => $depot,
            'pharmacies' => $pharmacies,
            'isEditing'  => true,
        ]);
    }

    public function update(Request $request, Depot $depot)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        if (! $isSuperAdmin && $user->pharmacy_id && $depot->pharmacy_id !== $user->pharmacy_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'address'       => 'nullable|string|max:255',
            'pharmacy_id'   => 'required|exists:pharmacies,id',
            'show_receipts' => 'sometimes|boolean',
        ]);

        if (! $isSuperAdmin && $user->pharmacy_id && (int) $validated['pharmacy_id'] !== (int) $user->pharmacy_id) {
            abort(403);
        }

        $depot->update($validated);

        return redirect()->route('depots.index')->with('success', 'Dépôt mis à jour avec succès.');
    }

    /**
     * Toggle the show_receipts flag for a depot (pharmacy admin only).
     */
    public function toggleReceipts(Request $request, Depot $depot)
    {
        $user = $request->user();

        // Only pharmacy admin/super_admin of the depot's pharmacy can toggle
        if (! $user->hasAnyRole(['super_admin', 'pharmacy_admin'])) {
            abort(403);
        }
        if ($user->pharmacy_id && $depot->pharmacy_id !== $user->pharmacy_id) {
            abort(403);
        }

        $depot->update(['show_receipts' => ! $depot->show_receipts]);

        if ($request->expectsJson()) {
            return response()->json([
                'show_receipts' => $depot->show_receipts,
            ]);
        }

        return back()->with('success', $depot->show_receipts
            ? 'Affichage des recettes activé pour ce dépôt.'
            : 'Affichage des recettes désactivé pour ce dépôt.');
    }

    /**
     * Toggle the show_stats flag for a depot (pharmacy admin only).
     */
    public function toggleStats(Request $request, Depot $depot)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['super_admin', 'pharmacy_admin'])) {
            abort(403);
        }
        if ($user->pharmacy_id && $depot->pharmacy_id !== $user->pharmacy_id) {
            abort(403);
        }

        $depot->update(['show_stats' => ! $depot->show_stats]);

        if ($request->expectsJson()) {
            return response()->json(['show_stats' => $depot->show_stats]);
        }

        return back()->with('success', $depot->show_stats
            ? 'Statistiques activées pour ce dépôt.'
            : 'Statistiques désactivées pour ce dépôt.');
    }

    public function destroy(Depot $depot)
    {
        $depot->delete();
        return redirect()->route('depots.index')->with('success', 'Dépôt supprimé avec succès.');
    }

    public function notifyLowStock(Request $request, Depot $depot)
    {
        $user = $request->user();
        if (! $user || $user->depot_id !== $depot->id) {
            abort(403);
        }

        $validated = $request->validate([
            'drug_id' => 'required|exists:drugs,id',
        ]);

        $drug = Drug::findOrFail($validated['drug_id']);

        $remaining = DrugUnit::query()
            ->where('drug_id', $drug->id)
            ->where('status', 'en_stock')
            ->where('current_location_type', 'depot')
            ->where('current_location_id', $depot->id)
            ->count();

        try {
            $recipients = collect();
            $recipients = $recipients->merge(User::role('super_admin')->get());
            $recipients = $recipients->merge(
                User::role(['pharmacy_admin', 'pharmacy_staff'])
                    ->where('pharmacy_id', $depot->pharmacy_id)
                    ->get()
            );

            $recipients = $recipients->unique('id')->values();

            foreach ($recipients as $recipient) {
                $recipient->notify(new DepotLowStockNotification($depot, $drug, $remaining));
            }
        } catch (\Throwable $e) {
            Log::warning('notifications.depot_low_stock_failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'depot_id' => $depot->id,
                'drug_id' => $drug->id,
                'remaining' => $remaining,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'remaining' => $remaining,
            ]);
        }

        return back()->with('success', "Notification envoyée (reste {$remaining}).");
    }
}
