<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Sale;
use App\Models\StockRequest;
use App\Models\User;
use App\Notifications\StockRequestedNotification;
use App\Notifications\StockRequestStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $requests = StockRequest::with(['user', 'depot', 'items.drug'])
            ->when(! $isSuperAdmin && $user->pharmacy_id, function ($q) use ($user) {
                $q->whereHas('depot', fn ($dq) => $dq->where('pharmacy_id', $user->pharmacy_id));
            })
            ->when($user->depot_id, fn ($q) => $q->where('depot_id', $user->depot_id))
            ->latest()
            ->paginate(10);

        // Daily stock report data
        $depotId = $user->depot_id;
        $today   = now()->toDateString();

        $stockQuery = DrugUnit::query()
            ->with('drug:id,name,form_med,dosage_med')
            ->when($depotId, fn ($q) => $q->where('current_location_type', 'depot')->where('current_location_id', $depotId));

        $availableStock = (clone $stockQuery)->where('status', 'en_stock')
            ->get()
            ->groupBy('drug_id')
            ->map(fn ($units) => [
                'drug'     => $units->first()->drug,
                'quantity' => $units->sum('quantite_actuelle'),
            ])->values();

        $soldToday = Sale::query()
            ->with('drugUnit.drug:id,name')
            ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
            ->whereDate('sold_at', $today)
            ->get()
            ->groupBy('drugUnit.drug_id')
            ->map(fn ($sales) => [
                'drug'     => $sales->first()->drugUnit?->drug,
                'quantity' => $sales->sum('quantity'),
                'revenue'  => $sales->sum('price'),
            ])->values();

        return Inertia::render('StockRequests/Index', [
            'requests'       => $requests,
            'availableStock' => $availableStock,
            'soldToday'      => $soldToday,
            'reportDate'     => now()->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drugs = Drug::select('id', 'name', 'form_med', 'dosage_med')->get();

        return Inertia::render('StockRequests/Create', [
            'drugs' => $drugs,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $stockRequest = StockRequest::create([
                'user_id' => $request->user()->id,
                'depot_id' => $request->user()->depot_id,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $stockRequest->items()->create([
                    'drug_id' => $item['drug_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // Notify Admins (Super Admin and Pharmacy Admin)
            $admins = User::role(['super_admin', 'pharmacy_admin'])->get();
            foreach ($admins as $admin) {
                // If the user is a pharmacy admin, ensure they belong to the same pharmacy as the depot
                if ($admin->hasRole('pharmacy_admin') && $stockRequest->depot && $admin->pharmacy_id !== $stockRequest->depot->pharmacy_id) {
                    continue;
                }
                
                $admin->notify(new StockRequestedNotification($stockRequest));
            }
        });

        return redirect()->route('stock-requests.index')
            ->with('success', 'Demande de stock créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(StockRequest $stockRequest)
    {
        $stockRequest->load(['user', 'depot', 'items.drug']);

        return Inertia::render('StockRequests/Show', [
            'stockRequest' => $stockRequest,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StockRequest $stockRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,completed',
        ]);

        $stockRequest->update([
            'status' => $validated['status'],
        ]);

        // Notify Requester
        $stockRequest->user->notify(new StockRequestStatusNotification($stockRequest, $validated['status']));

        return redirect()->back()
            ->with('success', 'Statut de la demande mis à jour.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockRequest $stockRequest)
    {
        $stockRequest->delete();

        return redirect()->route('stock-requests.index')
            ->with('success', 'Demande supprimée.');
    }
}
