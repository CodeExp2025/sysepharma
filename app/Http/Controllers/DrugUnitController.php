<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Sale;
use App\Models\Transfer;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DrugUnitController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    protected function authorizeCreateStock(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['super_admin', 'pharmacy_admin', 'pharmacy_staff'])) {
            abort(403);
        }
    }

    protected function authorizeManageStock(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['super_admin', 'pharmacy_admin'])) {
            abort(403);
        }
    }

    /**
     * Generate a unique barcode, auto-suffixing if the base already exists.
     * ABC123 → ABC123 (if free) | ABC123-002, ABC123-003, … (if taken)
     */
    protected function generateUniqueBarcode(string $base): string
    {
        $existing = DrugUnit::where('barcode', $base)
            ->orWhere('barcode', 'like', $base . '-%')
            ->pluck('barcode')
            ->toArray();

        if (empty($existing)) {
            return $base;
        }

        $maxNum = 0;
        foreach ($existing as $bc) {
            if ($bc === $base) {
                $maxNum = max($maxNum, 1);
            } elseif (preg_match('/^' . preg_quote($base, '/') . '-(\d+)$/', $bc, $m)) {
                $maxNum = max($maxNum, (int) $m[1]);
            }
        }

        return $base . '-' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $canViewAll   = $user->hasAnyRole(['super_admin', 'pharmacy_admin', 'pharmacy_staff']);

        $scope      = $request->query('scope', $canViewAll ? 'all' : null);
        $depotId    = $request->query('depot_id');
        $pharmacyId = $request->query('pharmacy_id');
        $search     = $request->query('search');

        $query = DrugUnit::query()->with('drug.category');

        // ── Pharmacy isolation ──────────────────────────────────────────────
        if (! $isSuperAdmin && $user->pharmacy_id) {
            $pharmacyDepotIds = Depot::where('pharmacy_id', $user->pharmacy_id)->pluck('id');

            $query->where(function ($q) use ($user, $pharmacyDepotIds) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('current_location_type', 'pharmacy')
                       ->where('current_location_id', $user->pharmacy_id);
                })->orWhere(function ($q2) use ($pharmacyDepotIds) {
                    $q2->where('current_location_type', 'depot')
                       ->whereIn('current_location_id', $pharmacyDepotIds);
                });
            });
        }

        if (! $canViewAll) {
            if ($user->depot_id) {
                $query->where('current_location_type', 'depot')
                      ->where('current_location_id', $user->depot_id);
            } else {
                $query->where('current_location_type', 'pharmacy')
                      ->where('current_location_id', $user->pharmacy_id ?? 1);
            }
        } else {
            if ($scope === 'depot' && $depotId) {
                $query->where('current_location_type', 'depot')
                      ->where('current_location_id', (int) $depotId);
            } elseif ($scope === 'pharmacy') {
                $query->where('current_location_type', 'pharmacy');
                $filterPharmacyId = $pharmacyId ?: ($isSuperAdmin ? null : $user->pharmacy_id);
                if ($filterPharmacyId) {
                    $query->where('current_location_id', (int) $filterPharmacyId);
                }
            }
        }

        // ── Search ──────────────────────────────────────────────────────────
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('drug_units.barcode COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"])
                  ->orWhereHas('drug', fn ($dq) => $dq->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]));
            });
        }

        $sort      = $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'drug_name') {
            $query->leftJoin('drugs', 'drugs.id', '=', 'drug_units.drug_id')
                  ->select('drug_units.*')
                  ->orderBy('drugs.name', $direction);
        } elseif ($sort === 'location') {
            $query->orderBy('current_location_type', $direction)
                  ->orderBy('current_location_id', $direction);
        } elseif (in_array($sort, ['created_at', 'expiration_date', 'price', 'status', 'barcode'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $depotQuery = Depot::query();
        if (! $isSuperAdmin && $user->pharmacy_id) {
            $depotQuery->where('pharmacy_id', $user->pharmacy_id);
        }
        $depots     = $depotQuery->select('id', 'name')->orderBy('name')->get();
        $depotNames = $depots->pluck('name', 'id');

        $stockByDrug = [];
        if ($user->depot_id) {
            $stockByDrug = DrugUnit::query()
                ->join('drugs', 'drugs.id', '=', 'drug_units.drug_id')
                ->leftJoin('categories', 'categories.id', '=', 'drugs.category_id')
                ->where('drug_units.status', 'en_stock')
                ->where('drug_units.current_location_type', 'depot')
                ->where('drug_units.current_location_id', $user->depot_id)
                ->select([
                    'drugs.id as drug_id',
                    'drugs.name as drug_name',
                    'categories.name as category_name',
                    DB::raw('SUM(drug_units.quantite_actuelle) as remaining'),
                ])
                ->groupBy('drugs.id', 'drugs.name', 'categories.name')
                ->orderBy('remaining', 'asc')
                ->get();
        }

        $today          = now()->toDateString();
        $reportDepotId  = $user->depot_id;
        $availableStock = DrugUnit::query()
            ->with('drug:id,name,form_med,dosage_med')
            ->when($reportDepotId, fn ($q) => $q->where('current_location_type', 'depot')->where('current_location_id', $reportDepotId))
            ->where('status', 'en_stock')
            ->get()
            ->groupBy('drug_id')
            ->map(fn ($units) => [
                'drug'     => $units->first()->drug,
                'quantity' => $units->sum('quantite_actuelle'),
            ])
            ->values();

        $soldToday = Sale::query()
            ->with('drugUnit.drug:id,name')
            ->when($reportDepotId, fn ($q) => $q->where('depot_id', $reportDepotId))
            ->whereDate('sold_at', $today)
            ->get()
            ->groupBy(fn ($sale) => $sale->drugUnit?->drug_id)
            ->map(fn ($sales) => [
                'drug'     => $sales->first()->drugUnit?->drug,
                'quantity' => $sales->sum('quantity'),
                'revenue'  => $sales->sum('price'),
            ])
            ->values();

        return Inertia::render('DrugUnits/Index', [
            'drugUnits' => $query
                ->paginate(20)
                ->through(function (DrugUnit $unit) use ($depotNames) {
                    $locationLabel = $unit->current_location_type === 'depot'
                        ? ('Dépôt: ' . ($depotNames[$unit->current_location_id] ?? ('#' . $unit->current_location_id)))
                        : 'Pharmacie';

                    return [
                        'id'                    => $unit->id,
                        'uuid'                  => $unit->uuid,
                        'barcode'               => $unit->barcode,
                        'quantite_contenu'      => $unit->quantite_contenu,
                        'quantite_actuelle'     => $unit->quantite_actuelle,
                        'expiration_date'       => $unit->expiration_date,
                        'status'                => $unit->status,
                        'price'                 => $unit->price,
                        'drug'                  => $unit->drug,
                        'current_location_type' => $unit->current_location_type,
                        'current_location_id'   => $unit->current_location_id,
                        'location_label'        => $locationLabel,
                    ];
                }),
            'depots'         => $depots,
            'filters'        => [
                'scope'       => $scope,
                'depot_id'    => $depotId,
                'pharmacy_id' => $pharmacyId,
                'sort'        => $sort,
                'direction'   => $direction,
                'search'      => $search,
            ],
            'canViewAll'       => $canViewAll,
            'currentDepotId'   => $user->depot_id,
            'currentDepotUuid' => $user->depot?->uuid,
            'stockByDrug'    => $stockByDrug,
            'availableStock' => $availableStock,
            'soldToday'      => $soldToday,
            'reportDate'     => now()->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm'),
        ]);
    }

    public function create()
    {
        $this->authorizeCreateStock(request());

        return Inertia::render('DrugUnits/Create', [
            'drugs' => Drug::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeCreateStock($request);

        $validated = $request->validate([
            'drug_id'          => 'required|exists:drugs,id',
            'barcode'          => 'required|string',
            'expiration_date'  => 'required|date|after:today',
            'price'            => 'required|numeric|min:0',
            'quantite_contenu' => 'required|integer|min:1',
            'quantity'         => 'nullable|integer|min:1|max:500',
        ]);

        $drug  = Drug::findOrFail($validated['drug_id']);
        $count = (int) ($validated['quantity'] ?? 1);
        $base  = $validated['barcode'];

        for ($i = 0; $i < $count; $i++) {
            $validated['barcode'] = $this->generateUniqueBarcode($base);
            $this->stockService->createDrugUnit($drug, $validated, $request->user());
        }

        $msg = $count > 1 ? "{$count} unités ajoutées avec succès." : 'Unité ajoutée avec succès.';

        return redirect()->back()->with('success', $msg);
    }

    public function edit(DrugUnit $drugUnit)
    {
        $this->authorizeManageStock(request());

        return Inertia::render('DrugUnits/Edit', [
            'drugUnit' => array_merge($drugUnit->load('drug.category')->toArray(), []),
            'drugs'    => Drug::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, DrugUnit $drugUnit)
    {
        $this->authorizeManageStock($request);

        $validated = $request->validate([
            'drug_id'          => 'required|exists:drugs,id',
            'barcode'          => 'required|string',
            'expiration_date'  => 'required|date',
            'price'            => 'required|numeric|min:0',
            'quantite_contenu' => 'required|integer|min:1',
            'status'           => 'required|in:en_stock,vendue,perimee,retiree',
        ]);

        // If barcode changed, auto-suffix if already taken by another unit
        if ($validated['barcode'] !== $drugUnit->barcode) {
            $validated['barcode'] = $this->generateUniqueBarcode($validated['barcode']);
        }

        $drugUnit->update($validated);

        return redirect()->route('drug-units.index')->with('success', 'Unité mise à jour avec succès.');
    }

    public function destroy(DrugUnit $drugUnit)
    {
        $this->authorizeManageStock(request());

        $drugUnit->delete();

        return redirect()->back()->with('success', 'Unité supprimée avec succès.');
    }

    /**
     * Get stock content with transfer traceability for the current location.
     */
    public function stockContent(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $canViewAll   = $user->hasAnyRole(['super_admin', 'pharmacy_admin', 'pharmacy_staff']);

        $scope   = $request->query('scope', $canViewAll ? 'all' : 'pharmacy');
        $depotId = $request->query('depot_id');

        // Build base query for drug units based on scope
        $unitsQuery = DrugUnit::query()
            ->with('drug.category')
            ->where('status', 'en_stock');

        if (! $isSuperAdmin && $user->pharmacy_id) {
            $pharmacyDepotIds = Depot::where('pharmacy_id', $user->pharmacy_id)->pluck('id');
            $unitsQuery->where(function ($q) use ($user, $pharmacyDepotIds) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('current_location_type', 'pharmacy')
                       ->where('current_location_id', $user->pharmacy_id);
                })->orWhere(function ($q2) use ($pharmacyDepotIds) {
                    $q2->where('current_location_type', 'depot')
                       ->whereIn('current_location_id', $pharmacyDepotIds);
                });
            });
        }

        // Apply scope filter
        if ($scope === 'pharmacy') {
            $unitsQuery->where('current_location_type', 'pharmacy');
            if (! $isSuperAdmin) {
                $unitsQuery->where('current_location_id', $user->pharmacy_id);
            }
        } elseif ($scope === 'depot' && $depotId) {
            $unitsQuery->where('current_location_type', 'depot')
                       ->where('current_location_id', $depotId);
        } elseif ($scope === 'depot' && $user->depot_id) {
            $unitsQuery->where('current_location_type', 'depot')
                       ->where('current_location_id', $user->depot_id);
        }

        $units = $unitsQuery->get();

        // Aggregate stock content by drug
        $stockContent = $units->groupBy('drug_id')->map(function ($drugUnits) {
            $drug = $drugUnits->first()->drug;
            $totalQty = $drugUnits->sum('quantite_actuelle');
            $avgPrice = $drugUnits->avg('price');

            return [
                'drug_id'       => $drug->id,
                'drug_name'     => $drug->name,
                'dosage'        => $drug->dosage_med,
                'category'      => $drug->category?->name,
                'total_quantity'=> $totalQty,
                'avg_price'     => round($avgPrice, 2),
            ];
        })->values();

        // Get transfer history based on location scope
        $transferQuery = Transfer::query()
            ->with(['items', 'performer', 'depot', 'fromPharmacy']);

        if ($scope === 'pharmacy' || ($scope === 'all' && ! $depotId)) {
            // For pharmacy: show transfers FROM this pharmacy TO depots (sent)
            // and transfers TO this pharmacy (received) - but transfers are only pharmacy -> depot
            $pharmacyId = $user->pharmacy_id;

            $sentTransfers = (clone $transferQuery)
                ->where('from_pharmacy_id', $pharmacyId)
                ->orderBy('performed_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn ($t) => [
                    'id'               => $t->id,
                    'to_depot_name'    => $t->depot?->name ?? 'Dépôt inconnu',
                    'item_count'       => $t->items_count ?? $t->items->count(),
                    'total_quantity'   => $t->items->sum('quantity'),
                    'performed_at'     => $t->performed_at,
                    'performer_name'   => $t->performer?->name ?? 'Système',
                ]);

            // For pharmacy received: transfers to pharmacy depots
            $pharmacyDepotIds = Depot::where('pharmacy_id', $pharmacyId)->pluck('id');
            $receivedTransfers = (clone $transferQuery)
                ->whereIn('to_depot_id', $pharmacyDepotIds)
                ->orderBy('performed_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn ($t) => [
                    'id'                 => $t->id,
                    'from_pharmacy_name' => $t->fromPharmacy?->name ?? 'Pharmacie inconnue',
                    'item_count'         => $t->items_count ?? $t->items->count(),
                    'total_quantity'     => $t->items->sum('quantity'),
                    'performed_at'       => $t->performed_at,
                    'performer_name'     => $t->performer?->name ?? 'Système',
                ]);
        } elseif ($scope === 'depot' && $depotId) {
            // For depot: show transfers TO this depot (received)
            // and transfers FROM this depot (sent) - though transfers are usually pharmacy -> depot
            $receivedTransfers = (clone $transferQuery)
                ->where('to_depot_id', $depotId)
                ->orderBy('performed_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn ($t) => [
                    'id'                 => $t->id,
                    'from_pharmacy_name' => $t->fromPharmacy?->name ?? 'Pharmacie inconnue',
                    'item_count'         => $t->items_count ?? $t->items->count(),
                    'total_quantity'     => $t->items->sum('quantity'),
                    'performed_at'       => $t->performed_at,
                    'performer_name'     => $t->performer?->name ?? 'Système',
                ]);

            // Depots typically don't send transfers, but show if any exist
            $sentTransfers = collect([]);
        } else {
            $sentTransfers = collect([]);
            $receivedTransfers = collect([]);
        }

        return response()->json([
            'stock_content' => $stockContent,
            'transfers'     => [
                'sent'     => $sentTransfers,
                'received' => $receivedTransfers,
            ],
        ]);
    }
}
