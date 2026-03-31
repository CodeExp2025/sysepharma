<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Sale;
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

    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $canViewAll   = $user->hasAnyRole(['super_admin', 'pharmacy_admin', 'pharmacy_staff']);

        $scope      = $request->query('scope', $canViewAll ? 'all' : null);
        $depotId    = $request->query('depot_id');
        $pharmacyId = $request->query('pharmacy_id');

        $query = DrugUnit::query()->with('drug.category');

        // ── Pharmacy isolation ──────────────────────────────────────────────
        // Non-super_admin users only see units in their active pharmacy's ecosystem
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
        // ────────────────────────────────────────────────────────────────────

        if (! $canViewAll) {
            // Depot staff: scope to their depot
            if ($user->depot_id) {
                $query->where('current_location_type', 'depot')
                      ->where('current_location_id', $user->depot_id);
            } else {
                $query->where('current_location_type', 'pharmacy')
                      ->where('current_location_id', $user->pharmacy_id ?? 1);
            }
        } else {
            // Pharmacy admin/staff: apply scope filter
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

        // Depots scoped to user's pharmacy (for filter dropdown)
        $depotQuery = Depot::query();
        if (! $isSuperAdmin && $user->pharmacy_id) {
            $depotQuery->where('pharmacy_id', $user->pharmacy_id);
        }
        $depots     = $depotQuery->select('id', 'name')->orderBy('name')->get();
        $depotNames = $depots->pluck('name', 'id');

        // Depot staff: stock-by-drug summary
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

        // Daily report data
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
            ],
            'canViewAll'     => $canViewAll,
            'currentDepotId' => $user->depot_id,
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
            'barcode'          => 'required|string|unique:drug_units,barcode',
            'expiration_date'  => 'required|date|after:today',
            'price'            => 'required|numeric|min:0',
            'quantite_contenu' => 'required|integer|min:1',
        ]);

        $drug = Drug::findOrFail($validated['drug_id']);

        $this->stockService->createDrugUnit($drug, $validated, $request->user());

        return redirect()->back()->with('success', 'Unité ajoutée avec succès.');
    }
}
