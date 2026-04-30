<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Disbursement;
use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Sale;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        // 1. Authorization Check
        if ($user->hasRole('depot_staff') && $user->depot_id) {
            $depot = Depot::find($user->depot_id);
            if ($depot && ! $depot->show_stats) {
                abort(403, 'Les statistiques sont désactivées pour ce dépôt.');
            }
        }

        $pharmacyId = $user->pharmacy_id ?? 0;
        $fromRaw = $request->input('from', now()->startOfMonth()->toDateTimeString());
        $toRaw   = $request->input('to', now()->toDateTimeString());

        // Support both date-only and datetime formats
        $from = str_contains($fromRaw, 'T') ? str_replace('T', ' ', $fromRaw) : $fromRaw . ' 00:00:00';
        $to   = str_contains($toRaw, 'T') ? str_replace('T', ' ', $toRaw) : $toRaw . ' 23:59:59';

        // 2. Fetch Depots and handle empty cases to prevent SQL "In ()" errors
        $depots = Depot::where('pharmacy_id', $pharmacyId)->get();
        $depotIds = $depots->pluck('id')->toArray();
        $validDepotIds = count($depotIds) > 0 ? $depotIds : [0];

        // 3. Stock by Drug and Location
        // Fix: Added quotes around 'pharmacy'/'depot' and ensured $pharmacyId is an integer
        $stockByDrug = DB::table('drug_units as du')
            ->join('drugs as d', 'd.id', '=', 'du.drug_id')
            ->leftJoin('categories as c', 'c.id', '=', 'd.category_id')
            ->where('du.status', 'en_stock')
            ->whereNull('du.deleted_at')
            ->whereNull('d.deleted_at')
            ->where(function ($q) use ($pharmacyId, $validDepotIds) {
                $q->where(fn ($q2) => $q2->where('du.current_location_type', 'pharmacy')->where('du.current_location_id', $pharmacyId))
                ->orWhere(fn ($q2) => $q2->where('du.current_location_type', 'depot')->whereIn('du.current_location_id', $validDepotIds));
            })
            ->select([
                'd.id as drug_id',
                'd.name as drug_name',
                'd.form_med',
                'd.dosage_med',
                'c.name as category_name',
                DB::raw("SUM(CASE WHEN du.current_location_type = 'pharmacy' AND du.current_location_id = " . (int)$pharmacyId . " THEN du.quantite_actuelle ELSE 0 END) as qty_pharmacy"),
                DB::raw("SUM(CASE WHEN du.current_location_type = 'depot' THEN du.quantite_actuelle ELSE 0 END) as qty_depots"),
                DB::raw('SUM(du.quantite_actuelle) as qty_total'),
                DB::raw('COUNT(DISTINCT du.id) as units_count'),
            ])
            ->groupBy('d.id', 'd.name', 'd.form_med', 'd.dosage_med', 'c.name')
            ->orderBy('qty_total', 'desc')
            ->get();

        // 4. Per-depot breakdown
        $stockPerDepot = [];
        if (count($depotIds)) {
            $rows = DB::table('drug_units as du')
                ->join('drugs as d', 'd.id', '=', 'du.drug_id')
                ->where('du.status', 'en_stock')
                ->whereNull('du.deleted_at')
                ->where('du.current_location_type', 'depot')
                ->whereIn('du.current_location_id', $depotIds)
                ->select('d.id as drug_id', 'du.current_location_id as depot_id', DB::raw('SUM(du.quantite_actuelle) as qty'))
                ->groupBy('d.id', 'du.current_location_id')
                ->get();
                
            foreach ($rows as $row) {
                $stockPerDepot[$row->drug_id][$row->depot_id] = $row->qty;
            }
        }

        // 5. Daily Revenue by Depot
        $dailyRevenue = DB::table('sales as s')
            ->join('depots as dep', 'dep.id', '=', 's.depot_id')
            ->whereIn('s.depot_id', $validDepotIds)
            ->whereBetween('s.created_at', [$from, $to])
            ->select([
                DB::raw('DATE(s.created_at) as date'),
                's.depot_id',
                'dep.name as depot_name',
                DB::raw('SUM(s.price) as revenue'),
                DB::raw('COUNT(DISTINCT COALESCE(s.transaction_id, CAST(s.id AS CHAR))) as sales_count'),
            ])
            ->groupBy(DB::raw('DATE(s.created_at)'), 's.depot_id', 'dep.name')
            ->orderBy('date', 'desc')
            ->get();

        // 5b. Daily Disbursements by Depot
        $dailyDisbursements = DB::table('disbursements as d')
            ->join('users as u', 'u.id', '=', 'd.initiated_by')
            ->leftJoin('depots as dep', 'dep.id', '=', 'u.depot_id')
            ->join('disbursement_items as di', 'di.disbursement_id', '=', 'd.id')
            ->where(function ($q) use ($validDepotIds, $pharmacyId) {
                $q->whereIn('dep.id', $validDepotIds)
                  ->orWhere(function ($q2) use ($pharmacyId) {
                      $q2->whereNull('dep.id')->where('u.pharmacy_id', $pharmacyId);
                  });
            })
            ->whereBetween('d.performed_at', [$from, $to])
            ->select([
                DB::raw('DATE(d.performed_at) as date'),
                DB::raw('COALESCE(dep.id, 0) as depot_id'),
                DB::raw('COALESCE(dep.name, "Sans dépôt") as depot_name'),
                DB::raw('SUM(di.quantite * di.prix_unitaire) as disbursement_total'),
                DB::raw('COUNT(DISTINCT d.id) as disbursement_count'),
            ])
            ->groupBy(DB::raw('DATE(d.performed_at)'), DB::raw('COALESCE(dep.id, 0)'), DB::raw('COALESCE(dep.name, "Sans dépôt")'))
            ->orderBy('date', 'desc')
            ->get();

        // 5c. Top Disbursers (who disburses the most)
        $topDisbursers = DB::table('disbursements as d')
            ->join('users as u', 'u.id', '=', 'd.initiated_by')
            ->join('disbursement_items as di', 'di.disbursement_id', '=', 'd.id')
            ->whereBetween('d.performed_at', [$from, $to])
            ->where('u.pharmacy_id', $pharmacyId)
            ->select([
                'u.id as user_id',
                'u.name as user_name',
                DB::raw('SUM(di.quantite * di.prix_unitaire) as total_disbursed'),
                DB::raw('COUNT(DISTINCT d.id) as disbursement_count'),
            ])
            ->groupBy('u.id', 'u.name')
            ->orderBy('total_disbursed', 'desc')
            ->limit(10)
            ->get();

        // 6. Top Selling Drugs
        $topDrugs = DB::table('sales as s')
            ->join('drug_units as du', 'du.id', '=', 's.drug_unit_id')
            ->join('drugs as d', 'd.id', '=', 'du.drug_id')
            ->whereIn('s.depot_id', $validDepotIds)
            ->whereBetween('s.created_at', [$from, $to])
            ->select([
                'd.id as drug_id',
                'd.name as drug_name',
                'd.form_med',
                'd.dosage_med',
                DB::raw('SUM(s.quantity) as total_qty'),
                DB::raw('SUM(s.price) as total_revenue'),
                DB::raw('COUNT(DISTINCT COALESCE(s.transaction_id, CAST(s.id AS CHAR))) as sales_count'),
            ])
            ->groupBy('d.id', 'd.name', 'd.form_med', 'd.dosage_med')
            ->orderBy('total_qty', 'desc')
            ->limit(20)
            ->get();

        // 7. Top Sellers (Users)
        $topSellers = DB::table('sales as s')
            ->join('users as u', 'u.id', '=', 's.sold_by')
            ->join('depots as dep', 'dep.id', '=', 's.depot_id')
            ->whereIn('s.depot_id', $validDepotIds)
            ->whereBetween('s.created_at', [$from, $to])
            ->select([
                'u.id as user_id',
                'u.name as user_name',
                'dep.name as depot_name',
                DB::raw('SUM(s.price) as total_revenue'),
                DB::raw('COUNT(DISTINCT COALESCE(s.transaction_id, CAST(s.id AS CHAR))) as sales_count'),
                DB::raw('SUM(s.quantity) as total_qty'),
            ])
            ->groupBy('u.id', 'u.name', 'dep.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        // 8. Near Expiry (< 90 days)
        $nearExpiry = DB::table('drug_units as du')
            ->join('drugs as d', 'd.id', '=', 'du.drug_id')
            ->leftJoin('categories as c', 'c.id', '=', 'd.category_id')
            ->where('du.status', 'en_stock')
            ->whereNull('du.deleted_at')
            ->whereRaw('du.expiration_date <= DATE_ADD(NOW(), INTERVAL 90 DAY)')
            ->whereRaw('du.expiration_date >= NOW()')
            ->where(function ($q) use ($pharmacyId, $validDepotIds) {
                $q->where(fn ($q2) => $q2->where('du.current_location_type', 'pharmacy')->where('du.current_location_id', $pharmacyId))
                ->orWhere(fn ($q2) => $q2->where('du.current_location_type', 'depot')->whereIn('du.current_location_id', $validDepotIds));
            })
            ->select([
                'du.id',
                'du.barcode',
                'du.expiration_date',
                'du.quantite_actuelle',
                'du.current_location_type',
                'du.current_location_id',
                'd.name as drug_name',
                'd.form_med',
                'c.name as category_name',
            ])
            ->orderBy('du.expiration_date', 'asc')
            ->get();

        // 9. Transfer Stats
        $transferStats = DB::table('transfers as t')
            ->join('depots as dep', 'dep.id', '=', 't.to_depot_id')
            ->where('dep.pharmacy_id', $pharmacyId)
            ->whereBetween('t.performed_at', [$from, $to])
            ->select([
                't.id',
                DB::raw('DATE(t.performed_at) as date'),
                't.to_depot_id as depot_id',
                'dep.name as depot_name',
                't.items_count',
                't.status',
            ])
            ->orderBy('t.performed_at', 'desc')
            ->get();

        $transferIds = $transferStats->pluck('id')->toArray();
        $transferQtys = [];
        if (count($transferIds)) {
            $rows = DB::table('transfer_items')
                ->whereIn('transfer_id', $transferIds)
                ->select('transfer_id', DB::raw('SUM(quantity) as total_qty'))
                ->groupBy('transfer_id')
                ->get();
            foreach ($rows as $row) {
                $transferQtys[$row->transfer_id] = $row->total_qty;
            }
        }

        // 10. Totals calculation
        $totals = [
            'revenue'             => $dailyRevenue->sum('revenue'),
            'disbursements'       => $dailyDisbursements->sum('disbursement_total'),
            'net_revenue'         => $dailyRevenue->sum('revenue') - $dailyDisbursements->sum('disbursement_total'),
            'sales_count'         => $dailyRevenue->sum('sales_count'),
            'disbursement_count'  => $dailyDisbursements->sum('disbursement_count'),
            'stock_units'         => $stockByDrug->sum('units_count'),
            'stock_qty'           => $stockByDrug->sum('qty_total'),
            'near_expiry'         => $nearExpiry->count(),
            'transfers'           => $transferStats->count(),
            'transfer_items'      => $transferStats->sum('items_count'),
        ];

        return Inertia::render('Stats/Index', [
            'pharmacy'            => $user->pharmacy,
            'depots'              => $depots,
            'stockByDrug'         => $stockByDrug,
            'stockPerDepot'       => $stockPerDepot,
            'dailyRevenue'        => $dailyRevenue,
            'dailyDisbursements'  => $dailyDisbursements,
            'topDrugs'            => $topDrugs,
            'topSellers'          => $topSellers,
            'topDisbursers'       => $topDisbursers,
            'nearExpiry'          => $nearExpiry,
            'transferStats'       => $transferStats,
            'transferQtys'        => $transferQtys,
            'totals'              => $totals,
            'filters'             => ['from' => $fromRaw, 'to' => $toRaw],
        ]);
    }
}
