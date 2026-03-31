<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Sale;
use Illuminate\Http\Request;
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

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'lowStockDrugs' => $lowStockDrugs,
        ]);
    }
}
