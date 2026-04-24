<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Sale;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $locationType = 'pharmacy';
        $locationId = $user->pharmacy_id ?? 1;
        $stockScopeLabel = 'Stock Pharmacie';

        if ($user->depot_id) {
            $locationType = 'depot';
            $locationId = $user->depot_id;
            $stockScopeLabel = 'Stock Dépôt';
        }

        $stats = [
            'total_units' => DrugUnit::where('current_location_type', $locationType)
                ->where('current_location_id', $locationId)
                ->count(),
            'total_stock_pharmacy' => DrugUnit::where('current_location_type', $locationType)
                ->where('current_location_id', $locationId)
                ->where('status', 'en_stock')
                ->count(),
            'sold_units' => DrugUnit::where('current_location_type', $locationType)
                ->where('current_location_id', $locationId)
                ->where('status', 'vendue')
                ->count(),
            'reserved_units' => 0,
            'expiring_soon' => DrugUnit::where('current_location_type', $locationType)
                ->where('current_location_id', $locationId)
                ->where('status', 'en_stock')
                ->whereBetween('expiration_date', [today(), today()->addDays(30)])
                ->count(),
            'total_sales_today' => Sale::whereDate('sold_at', today())
                ->when($user->depot_id, fn ($q) => $q->where('depot_id', $user->depot_id))
                ->count(),
            'stock_scope_label' => $stockScopeLabel,
        ];

        $lowStockDrugs = Drug::withCount(['drugUnits as current_stock' => function ($query) use ($locationType, $locationId) {
            $query
                ->where('status', 'en_stock')
                ->where('current_location_type', $locationType)
                ->where('current_location_id', $locationId);
        }])
            ->whereRaw(
                '(select count(*) from drug_units where drug_units.drug_id = drugs.id and status = ? and current_location_type = ? and current_location_id = ? and drug_units.deleted_at is null) <= drugs.min_stock',
                ['en_stock', $locationType, $locationId]
            )
            ->orderBy('current_stock', 'asc')
            ->take(10)
            ->get();

        // Sales data for the last 7 days
        $salesData = collect(range(6, 0))->map(function ($days) use ($user) {
            $date = Carbon::today()->subDays($days);
            $query = Sale::whereDate('sold_at', $date);

            if ($user->depot_id) {
                $query->where('depot_id', $user->depot_id);
            } elseif ($user->pharmacy_id) {
                $query->whereHas('drugUnit', function ($q) use ($user) {
                    $q->where('current_location_type', 'pharmacy')
                      ->where('current_location_id', $user->pharmacy_id);
                });
            }

            return [
                'date' => $date->translatedFormat('D d M'),
                'count' => $query->count(),
            ];
        })->values()->all();

        // Sales by category
        $categoryStats = Category::select('categories.name')
            ->selectRaw('COUNT(sales.id) as count')
            ->leftJoin('drugs', 'drugs.category_id', '=', 'categories.id')
            ->leftJoin('drug_units', 'drug_units.drug_id', '=', 'drugs.id')
            ->leftJoin('sales', function ($join) use ($user) {
                $join->on('sales.drug_unit_id', '=', 'drug_units.id');
                if ($user->depot_id) {
                    $join->where('sales.depot_id', $user->depot_id);
                }
            })
            ->where('drug_units.current_location_type', $locationType)
            ->where('drug_units.current_location_id', $locationId)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(fn ($c) => ['name' => $c->name, 'count' => (int) $c->count]);

        // Stock by location for overview chart
        $pharmacyStock = DrugUnit::where('current_location_type', 'pharmacy')
            ->where('status', 'en_stock')
            ->count();

        $depotStock = DrugUnit::where('current_location_type', 'depot')
            ->where('status', 'en_stock')
            ->count();

        $pharmacySold = DrugUnit::where('current_location_type', 'pharmacy')
            ->where('status', 'vendue')
            ->count();

        $depotSold = DrugUnit::where('current_location_type', 'depot')
            ->where('status', 'vendue')
            ->count();

        $stockByLocation = [
            'pharmacy' => [
                'label' => 'Pharmacie',
                'en_stock' => $pharmacyStock,
                'vendue' => $pharmacySold,
            ],
            'depots' => [
                'label' => 'Dépôts',
                'en_stock' => $depotStock,
                'vendue' => $depotSold,
            ],
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'lowStockDrugs' => $lowStockDrugs,
            'salesData' => $salesData,
            'categoryStats' => $categoryStats,
            'stockByLocation' => $stockByLocation,
        ]);
    }
}
